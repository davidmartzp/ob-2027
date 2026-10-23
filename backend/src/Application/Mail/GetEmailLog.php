<?php

declare(strict_types=1);

namespace App\Application\Mail;

use App\Application\Port\MailQueue;
use App\Application\Shared\Validator;

/** GET /emails/log: what went out, what is pending and what failed. */
final readonly class GetEmailLog
{
    public function __construct(private MailQueue $mail)
    {
    }

    /** @param array<string, mixed> $query @return array<string, mixed> */
    public function execute(array $query): array
    {
        $data = Validator::validate($query, ['type' => 'string|max:40', 'page' => 'int:1,100000', 'per_page' => 'int:1,200']);

        return $this->mail->log($data['type'], $data['page'] ?? 1, $data['per_page'] ?? 50);
    }
}
