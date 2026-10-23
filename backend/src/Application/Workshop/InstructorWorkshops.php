<?php

declare(strict_types=1);

namespace App\Application\Workshop;

use App\Application\Shared\Validator;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;
use App\Domain\Workshop\WorkshopRepository;
use App\Domain\Workshop\WorkshopStatus;

/**
 * An attendee proposes workshops and lists their own (`/attendees/{id}/workshops`), and
 * finds them by email (`POST /workshops/by-email`). No sign-in, as in the legacy site.
 */
final readonly class InstructorWorkshops
{
    private const int MAX_PER_REQUEST = 10;

    public function __construct(
        private WorkshopRepository $workshops,
        private WorkshopInput $input,
        private WorkshopView $view,
        private AttendeeRepository $attendees,
    ) {
    }

    /**
     * @param array<string, mixed> $input `{"workshops": [{name, level, public, duration, observations}, …]}`
     * @return list<array<string, mixed>>
     */
    public function propose(int $attendeeId, array $input): array
    {
        if ($this->attendees->findActive($attendeeId) === null) {
            throw new NotFoundException('No existe ese asistente.');
        }
        $items = $input['workshops'] ?? null;
        if (!is_array($items) || !array_is_list($items) || $items === [] || count($items) > self::MAX_PER_REQUEST) {
            throw ValidationException::field('workshops', 'Envía entre 1 y ' . self::MAX_PER_REQUEST . ' talleres.');
        }

        // Validate everything before saving anything.
        $rows = [];
        foreach ($items as $index => $item) {
            try {
                $rows[] = $this->input->columns(is_array($item) ? $item : []);
            } catch (ValidationException $error) {
                throw new ValidationException(array_combine(
                    array_map(static fn (string $field): string => "workshops.{$index}.{$field}", array_keys($error->fields)),
                    $error->fields,
                ));
            }
        }

        $created = array_map(fn (array $row) => $this->workshops->create($row + [
            'id_attendee' => $attendeeId,
            'status' => WorkshopStatus::Active->value,
        ]), $rows);

        return $this->view->forOwner($created);
    }

    /** @return list<array<string, mixed>> */
    public function list(int $attendeeId): array
    {
        return $this->view->forOwner($this->workshops->listByAttendee($attendeeId));
    }

    /**
     * Everyone registered with that email (families) and their workshops (legacy POST /workshops).
     *
     * @param array<string, mixed> $input
     * @return list<array{id: int, name: string, workshops: list<array<string, mixed>>}>
     */
    public function byEmail(array $input): array
    {
        $email = Validator::validate($input, ['email' => 'required|email|max:255'])['email'];

        return array_map(fn ($attendee): array => [
            'id' => $attendee->id,
            'name' => $attendee->fullName(),
            'workshops' => $this->list($attendee->id),
        ], $this->attendees->findActiveByEmail($email));
    }
}
