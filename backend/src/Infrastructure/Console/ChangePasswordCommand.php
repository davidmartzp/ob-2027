<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\ChangeUserPassword;
use InvalidArgumentException;

final readonly class ChangePasswordCommand implements Command
{
    public function __construct(private ChangeUserPassword $changePassword)
    {
    }

    public function name(): string
    {
        return 'user:password';
    }

    public function description(): string
    {
        return "Change a CMS user's password and close their sessions: user:password <email>";
    }

    public function run(array $arguments): int
    {
        $email = $arguments[0] ?? throw new InvalidArgumentException('Usage: php bin/console user:password <email>');

        $user = $this->changePassword->execute($email, PasswordPrompt::ask());
        echo "Password changed for {$user->email}; their open sessions were closed.\n";

        return 0;
    }
}
