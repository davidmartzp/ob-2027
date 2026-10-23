<?php

declare(strict_types=1);

namespace App\Application\Mail;

/** The team's inbox (MAIL_TEAM_ADDRESS), which receives the notice of each new registration. */
final readonly class TeamMailbox
{
    public function __construct(public string $address)
    {
    }
}
