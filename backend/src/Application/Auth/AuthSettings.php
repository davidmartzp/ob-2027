<?php

declare(strict_types=1);

namespace App\Application\Auth;

/** Authentication settings, built from the environment in `config/container.php`. */
final readonly class AuthSettings
{
    public function __construct(public int $cmsTokenHours = 12)
    {
    }
}
