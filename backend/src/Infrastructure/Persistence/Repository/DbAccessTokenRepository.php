<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Auth\AccessTokenRepository;
use App\Domain\Auth\Session;
use App\Domain\Auth\TokenType;
use DateTimeImmutable;
use Illuminate\Database\Connection;

/** Table `access_tokens` (replaces Laravel's `personal_access_tokens`). */
final readonly class DbAccessTokenRepository implements AccessTokenRepository
{
    private const string DATETIME = 'Y-m-d H:i:s';

    public function __construct(private Connection $db)
    {
    }

    public function store(TokenType $type, int $subjectId, string $hash, DateTimeImmutable $expiresAt, DateTimeImmutable $now): void
    {
        $this->db->table('access_tokens')->insert([
            'type' => $type->value,
            'subject_id' => $subjectId,
            'hash' => $hash,
            'expires_at' => $expiresAt->format(self::DATETIME),
            'created_at' => $now->format(self::DATETIME),
        ]);
    }

    public function findActive(string $hash, DateTimeImmutable $now): ?Session
    {
        $row = $this->db->table('access_tokens')
            ->where('hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $now->format(self::DATETIME))
            ->first();
        if ($row === null) {
            return null;
        }
        $this->db->table('access_tokens')->where('id', $row->id)->update(['last_used_at' => $now->format(self::DATETIME)]);

        return new Session(TokenType::from($row->type), (int) $row->subject_id, (int) $row->id);
    }

    public function revoke(int $tokenId, DateTimeImmutable $now): void
    {
        $this->db->table('access_tokens')->where('id', $tokenId)->update(['revoked_at' => $now->format(self::DATETIME)]);
    }

    public function revokeAllFor(TokenType $type, int $subjectId, DateTimeImmutable $now): void
    {
        $this->db->table('access_tokens')
            ->where('type', $type->value)
            ->where('subject_id', $subjectId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $now->format(self::DATETIME)]);
    }
}
