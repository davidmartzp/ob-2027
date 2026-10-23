<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/**
 * Who a token belongs to. Only CMS users sign in: attendees act without a session,
 * identified by email/id as in the legacy system (decision of 2026-10-23, design.md D5).
 */
enum TokenType: string
{
    case Cms = 'cms';
}
