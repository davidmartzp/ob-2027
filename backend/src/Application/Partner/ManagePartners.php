<?php

declare(strict_types=1);

namespace App\Application\Partner;

use App\Application\Port\ImageStorage;
use App\Application\Shared\Validator;
use App\Domain\Partner\Partner;
use App\Domain\Partner\PartnerRepository;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;

/** Public partner list and its CMS administration. Deleting only deactivates. */
final readonly class ManagePartners
{
    public function __construct(
        private PartnerRepository $partners,
        private PartnerView $view,
        private ImageStorage $images,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function publicList(): array
    {
        return array_map($this->view->toArray(...), $this->partners->listActive());
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function create(array $input): array
    {
        return $this->view->toArray($this->partners->create(self::columns($input) + ['status' => 1]));
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function update(int $id, array $input): array
    {
        $this->find($id);

        return $this->view->toArray($this->partners->update($id, self::columns($input)));
    }

    /** Keeps the partner and its workshop links; it just stops being shown. */
    public function deactivate(int $id): void
    {
        $this->find($id);
        $this->partners->update($id, ['status' => 0]);
    }

    /** @return array{logo: string|null} */
    public function setLogo(int $id, string $bytes): array
    {
        $partner = $this->find($id);
        $file = $this->images->store($bytes, "partner-{$id}", 'logo');
        $this->partners->update($id, ['logo' => $file]);
        $this->images->delete($partner->logo);

        return ['logo' => $this->images->url($file)];
    }

    private function find(int $id): Partner
    {
        return $this->partners->find($id) ?? throw new NotFoundException('No existe ese socio.');
    }

    /** @param array<string, mixed> $input @return array{name: string, whatsapp: string, message: string} */
    private static function columns(array $input): array
    {
        $data = Validator::validate($input, [
            'name' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:15',
            'message' => 'required|string|max:1000',
        ]);
        if (preg_match('/^\d{8,15}$/', $data['whatsapp']) !== 1) {
            throw ValidationException::field('whatsapp', 'Escribe solo números en formato internacional, p. ej. 573001234567.');
        }

        return ['name' => $data['name'], 'whatsapp' => $data['whatsapp'], 'message' => $data['message']];
    }
}
