<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/**
 * Who gets a participant number (inherited rule): not staff, at least the minimum age
 * and not paying with an excluded method (support group, guests).
 */
final readonly class CodePolicy
{
    /** @param list<string> $excludedPaymentMethods */
    public function __construct(
        public int $minAge = 12,
        public array $excludedPaymentMethods = ['Grupo de apoyo', 'Invitado', 'Invitado(a)'],
    ) {
    }

    public function isEligible(int $age, bool $staff, ?string $paymethod): bool
    {
        return !$staff
            && $age >= $this->minAge
            && !in_array($paymethod, $this->excludedPaymentMethods, true);
    }

    public function allows(Attendee $attendee): bool
    {
        return $attendee->status !== AttendeeStatus::Deleted
            && $this->isEligible($attendee->age, $attendee->staff, $attendee->paymethod);
    }
}
