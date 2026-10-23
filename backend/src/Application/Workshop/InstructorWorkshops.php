<?php

declare(strict_types=1);

namespace App\Application\Workshop;

use App\Domain\Auth\Session;
use App\Domain\Shared\Exception\ValidationException;
use App\Domain\Workshop\WorkshopRepository;
use App\Domain\Workshop\WorkshopStatus;

/** An attendee proposes workshops and lists their own (`/attendees/{id}/workshops`). */
final readonly class InstructorWorkshops
{
    private const int MAX_PER_REQUEST = 10;

    public function __construct(
        private WorkshopRepository $workshops,
        private WorkshopInput $input,
        private WorkshopView $view,
    ) {
    }

    /**
     * @param array<string, mixed> $input `{"workshops": [{name, level, public, duration, observations}, …]}`
     * @return list<array<string, mixed>>
     */
    public function propose(Session $session, int $attendeeId, array $input): array
    {
        $session->requireAttendee($attendeeId);
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
    public function list(Session $session, int $attendeeId): array
    {
        $session->requireAttendee($attendeeId);

        return $this->view->forOwner($this->workshops->listByAttendee($attendeeId));
    }
}
