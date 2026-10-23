<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Auth\AccessLinkRepository;
use DateTimeImmutable;
use Illuminate\Database\Connection;

/** Table `access_links`. */
final readonly class DbAccessLinkRepository implements AccessLinkRepository
{
    private const string DATETIME = 'Y-m-d H:i:s';

    public function __construct(private Connection $db)
    {
    }

    public function create(string $hash, int $attendeeId, DateTimeImmutable $expiresAt, DateTimeImmutable $now): void
    {
        $this->db->table('access_links')->insert([
            'hash' => $hash,
            'id_attendee' => $attendeeId,
            'expires_at' => $expiresAt->format(self::DATETIME),
            'created_at' => $now->format(self::DATETIME),
        ]);
    }

    public function consume(string $hash, DateTimeImmutable $now): ?int
    {
        $link = $this->db->table('access_links')
            ->where('hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now->format(self::DATETIME))
            ->first();
        if ($link === null) {
            return null;
        }
        // The `used_at IS NULL` condition makes the update the real check: of two concurrent
        // redemptions only one changes the row.
        $updated = $this->db->table('access_links')
            ->where('id', $link->id)
            ->whereNull('used_at')
            ->update(['used_at' => $now->format(self::DATETIME)]);

        return $updated === 1 ? (int) $link->id_attendee : null;
    }
}
