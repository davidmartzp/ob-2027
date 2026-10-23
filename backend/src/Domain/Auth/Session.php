<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/** The signed-in CMS user of a request, resolved from a valid access token. */
final readonly class Session
{
    public function __construct(
        public TokenType $type,
        public int $subjectId,
        public int $tokenId,
    ) {
    }
}
