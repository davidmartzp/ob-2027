<?php

declare(strict_types=1);

namespace App\Application\Partner;

use App\Domain\Partner\PartnerRepository;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;
use App\Domain\Workshop\Workshop;
use App\Domain\Workshop\WorkshopRepository;

/** The partners shown with a workshop (`/workshops/{id}/partners`). */
final readonly class WorkshopPartners
{
    public function __construct(
        private PartnerRepository $partners,
        private WorkshopRepository $workshops,
        private PartnerView $view,
    ) {
    }

    /** @return list<array<string, mixed>> Active partners of the workshop. */
    public function list(int $workshopId): array
    {
        $this->findWorkshop($workshopId);

        return array_map($this->view->toArray(...), $this->partners->activeByWorkshop([$workshopId])[$workshopId] ?? []);
    }

    /**
     * Replaces the whole list (no sign-in, as in the legacy site).
     *
     * @param array<string, mixed> $input `{"partner_ids": [1, 2]}`
     * @return list<array<string, mixed>>
     */
    public function replace(int $workshopId, array $input): array
    {
        $this->findWorkshop($workshopId);

        $ids = $input['partner_ids'] ?? null;
        if (!is_array($ids) || !array_is_list($ids) || array_filter($ids, static fn (mixed $id): bool => !is_int($id) && !ctype_digit((string) $id)) !== []) {
            throw ValidationException::field('partner_ids', 'Envía la lista de ids de socios (puede ir vacía).');
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $missing = array_diff($ids, $this->partners->existingIds($ids));
        if ($missing !== []) {
            throw ValidationException::field('partner_ids', 'No existen los socios: ' . implode(', ', $missing) . '.');
        }

        $this->partners->replaceForWorkshop($workshopId, $ids);

        return $this->list($workshopId);
    }

    private function findWorkshop(int $id): Workshop
    {
        $workshop = $this->workshops->find($id);
        if ($workshop === null || $workshop->isWithdrawn()) {
            throw new NotFoundException('No existe ese taller.');
        }

        return $workshop;
    }
}
