<?php

declare(strict_types=1);

namespace App\Domain\Workshop;

/**
 * Allowed values for a workshop's level and audience (configurable). Legacy data uses
 * level P (principiante), I (intermedio), A (avanzado) and audience A (adultos), N (niños).
 */
final readonly class WorkshopOptions
{
    /**
     * @param list<string> $levels
     * @param list<string> $audiences
     */
    public function __construct(
        public array $levels = ['P', 'I', 'A'],
        public array $audiences = ['A', 'N'],
    ) {
    }
}
