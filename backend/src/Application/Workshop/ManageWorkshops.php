<?php

declare(strict_types=1);

namespace App\Application\Workshop;

use App\Application\Shared\Validator;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;
use App\Domain\Workshop\WorkshopRepository;

/** Public listing and CMS administration of workshops. */
final readonly class ManageWorkshops
{
    public function __construct(private WorkshopRepository $workshops, private WorkshopView $view)
    {
    }

    /** @return list<array<string, mixed>> GET /workshops: active ones, random order. */
    public function publicList(): array
    {
        return $this->view->forPublic($this->workshops->listActiveShuffled());
    }

    /** @return list<array<string, mixed>> GET /cms/workshops */
    public function cmsList(): array
    {
        return $this->view->forCms($this->workshops->listNotWithdrawn());
    }

    /**
     * PATCH /workshops/{id}: only the keys sent change (status, tickets, part, relation).
     * `part` 1 with no relation is a single-part workshop (all legacy rows); part 2 points to its part 1.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function patch(int $id, array $input): array
    {
        if ($this->workshops->find($id) === null) {
            throw new NotFoundException('No existe ese taller.');
        }
        $data = Validator::validate($input, [
            'status' => 'in:0,1,2',
            'tickets' => 'int:0,10000',
            'part' => 'in:1,2',
            'relation' => 'int:1,100000000',
        ]);
        $fields = [];
        foreach (['status', 'tickets', 'part', 'relation'] as $key) {
            if (array_key_exists($key, $input)) {
                $fields[$key] = $data[$key] === null ? null : (int) $data[$key];
            }
        }
        if (isset($fields['relation']) && ($fields['relation'] === $id || $this->workshops->find($fields['relation']) === null)) {
            throw ValidationException::field('relation', 'Debe ser otro taller existente.');
        }
        foreach (['status', 'tickets', 'part'] as $required) {
            if (array_key_exists($required, $fields) && $fields[$required] === null) {
                throw ValidationException::field($required, 'Valor no permitido.');
            }
        }

        return $this->view->forCms([$this->workshops->update($id, $fields)])[0];
    }
}
