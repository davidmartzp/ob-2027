<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Port\PasswordHasher;
use App\Application\Port\RateLimiter;
use App\Application\Shared\Validator;
use App\Domain\Auth\TokenType;
use App\Domain\Auth\UserRepository;
use App\Domain\Shared\Exception\UnauthorizedException;
use DateTimeInterface;

/** CMS sign-in with email and password. */
final readonly class Login
{
    private const int MAX_FAILURES = 5;
    private const int WINDOW_MINUTES = 15;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenService $tokens,
        private RateLimiter $limiter,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{access_token: string, token_type: string, expires_at: string, user: array{id: int, name: string, email: string}}
     */
    public function execute(array $input, string $ip): array
    {
        $data = Validator::validate($input, ['email' => 'required|email', 'password' => 'required|string']);
        $emailKey = 'login:email:' . $data['email'];
        $ipKey = 'login:ip:' . $ip;

        // Checked before the password: once over the limit, not even the right one gets in.
        $this->limiter->ensureBelow($emailKey, self::MAX_FAILURES, self::WINDOW_MINUTES);
        $this->limiter->ensureBelow($ipKey, self::MAX_FAILURES, self::WINDOW_MINUTES);

        $user = $this->users->findByEmail($data['email']);
        if (!$this->hasher->verify($data['password'], $user?->passwordHash) || $user === null) {
            $this->limiter->hit($emailKey);
            $this->limiter->hit($ipKey);
            throw new UnauthorizedException('Correo o contraseña incorrectos.');
        }

        $this->limiter->clear($emailKey);
        if ($this->hasher->needsRehash($user->passwordHash)) {
            $this->users->updatePasswordHash($user->id, $this->hasher->hash($data['password']));
        }

        $issued = $this->tokens->issue(TokenType::Cms, $user->id);

        return [
            'access_token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expires_at']->format(DateTimeInterface::ATOM),
            'user' => $user->toPublicArray(),
        ];
    }
}
