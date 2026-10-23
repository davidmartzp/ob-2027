<?php

declare(strict_types=1);

namespace App\Domain\Partner;

/** Writes take `partners` column values (name, logo, whatsapp, message, status). */
interface PartnerRepository
{
    /** Any status. */
    public function find(int $id): ?Partner;

    /** @return list<Partner> Active partners by name. */
    public function listActive(): array;

    /** @param array<string, mixed> $fields */
    public function create(array $fields): Partner;

    /** @param array<string, mixed> $fields */
    public function update(int $id, array $fields): Partner;

    /**
     * Active partners of each workshop.
     *
     * @param list<int> $workshopIds
     * @return array<int, list<Partner>> keyed by workshop id (workshops without partners are absent)
     */
    public function activeByWorkshop(array $workshopIds): array;

    /** @param list<int> $ids @return list<int> The ids among `$ids` that exist (any status). */
    public function existingIds(array $ids): array;

    /** Replaces the whole set of partners of a workshop. @param list<int> $partnerIds */
    public function replaceForWorkshop(int $workshopId, array $partnerIds): void;
}
