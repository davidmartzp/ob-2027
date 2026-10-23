<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Application\Shared\Validator;
use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Attendee\CodePolicy;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;

/**
 * CMS use cases over attendees: list, show, edit, delete (soft), bulk confirm and
 * participant numbers. Grouped because each one is a thin step over the repository.
 */
final readonly class ManageAttendees
{
    public function __construct(private AttendeeRepository $attendees, private CodePolicy $codes)
    {
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return array_map(AttendeeView::toArray(...), $this->attendees->listActive());
    }

    /** @return array<string, mixed> */
    public function show(int $id): array
    {
        return AttendeeView::toArray($this->find($id));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(int $id, array $input): array
    {
        $this->find($id);
        $fields = AttendeeInput::forCmsUpdate($input);
        if ($fields['code'] !== null && $this->attendees->codeTakenByOther($fields['code'], $id)) {
            throw new ConflictException("El número {$fields['code']} ya lo tiene otro asistente.");
        }

        return AttendeeView::toArray($this->attendees->update($id, $fields));
    }

    /** Soft delete: status 5; the row stays. */
    public function delete(int $id): void
    {
        $this->find($id);
        $this->attendees->update($id, ['status' => AttendeeStatus::Deleted->value]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{confirmed: int}
     */
    public function confirm(array $input): array
    {
        $ids = Validator::validate($input, ['ids' => 'required|list'])['ids'];

        return ['confirmed' => $this->attendees->confirmPending(array_map('intval', $ids))];
    }

    /** @return array{assigned: int} Numbers for eligible attendees still without one. */
    public function assignMissingCodes(): array
    {
        return ['assigned' => $this->attendees->assignCodes($this->codes, fromScratch: false)];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{assigned: int}
     */
    public function regenerateCodes(array $input): array
    {
        if (($input['confirmation'] ?? null) !== 'regenerate') {
            throw ValidationException::field('confirmation', 'Escriba «regenerate» para confirmar: se cambiarán los números de todos.');
        }

        return ['assigned' => $this->attendees->assignCodes($this->codes, fromScratch: true)];
    }

    private function find(int $id): Attendee
    {
        return $this->attendees->findActive($id) ?? throw new NotFoundException('No existe ese asistente.');
    }
}
