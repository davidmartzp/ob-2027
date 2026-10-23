<?php

declare(strict_types=1);

namespace App\Application\Auth;

/** Authentication settings, built from the environment in `config/container.php`. */
final readonly class AuthSettings
{
    public function __construct(
        public int $cmsTokenHours = 12,
        public int $attendeeTokenHours = 2,
        public int $accessLinkMinutes = 30,
        /** Site page that receives `?key=…` and redeems it at POST /auth/redeem-link. */
        public string $accessLinkUrl = 'http://localhost:4200/acceso',
    ) {
    }
}
