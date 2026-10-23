<?php

declare(strict_types=1);

namespace App\Application\Workshop;

use App\Application\Port\ImageStorage;
use App\Domain\Auth\Session;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Workshop\Workshop;
use App\Domain\Workshop\WorkshopRepository;
use App\Domain\Workshop\WorkshopStatus;

/** Edit, withdraw and set the photo of a workshop: its author or the CMS. */
final readonly class EditWorkshop
{
    public function __construct(
        private WorkshopRepository $workshops,
        private WorkshopInput $input,
        private WorkshopView $view,
        private ImageStorage $images,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(Session $session, int $id, array $input): array
    {
        $this->findEditable($session, $id);

        return $this->view->forOwner([$this->workshops->update($id, $this->input->columns($input))])[0];
    }

    public function withdraw(Session $session, int $id): void
    {
        $this->findEditable($session, $id);
        $this->workshops->update($id, ['status' => WorkshopStatus::Withdrawn->value]);
    }

    /** @return array{image: string|null} */
    public function setImage(Session $session, int $id, string $bytes): array
    {
        $workshop = $this->findEditable($session, $id);
        $file = $this->images->store($bytes, "workshop-{$id}");
        $this->workshops->update($id, ['image' => $file]);
        $this->images->delete($workshop->image);

        return ['image' => $this->images->url($file)];
    }

    private function findEditable(Session $session, int $id): Workshop
    {
        $workshop = $this->workshops->find($id);
        if ($workshop === null || $workshop->isWithdrawn()) {
            throw new NotFoundException('No existe ese taller.');
        }
        $session->requireCmsOrOwner((int) $workshop->attendeeId);

        return $workshop;
    }
}
