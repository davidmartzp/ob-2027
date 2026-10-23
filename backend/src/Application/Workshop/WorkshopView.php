<?php

declare(strict_types=1);

namespace App\Application\Workshop;

use App\Application\Partner\PartnerView;
use App\Application\Port\ImageStorage;
use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Partner\PartnerRepository;
use App\Domain\Workshop\Workshop;

/**
 * JSON shapes of workshops. The audience keeps the legacy key `public` (A adults,
 * N children) because the site and CMS already read it.
 */
final readonly class WorkshopView
{
    public function __construct(
        private ImageStorage $images,
        private AttendeeRepository $attendees,
        private PartnerRepository $partners,
        private PartnerView $partnerView,
    ) {
    }

    /**
     * @param list<Workshop> $workshops
     * @return list<array<string, mixed>> For the author: own data, status and partners.
     */
    public function forOwner(array $workshops): array
    {
        $partners = $this->partnersOf($workshops);

        return array_map(fn (Workshop $w): array => $this->base($w) + [
            'status' => $w->status->value,
            'partners' => $partners[$w->id] ?? [],
        ], $workshops);
    }

    /**
     * @param list<Workshop> $workshops
     * @return list<array<string, mixed>> Public listing: instructor name only, no contact data.
     */
    public function forPublic(array $workshops): array
    {
        $partners = $this->partnersOf($workshops);
        $instructors = $this->instructorsOf($workshops);

        return array_map(fn (Workshop $w): array => $this->base($w) + [
            'instructor' => isset($instructors[$w->attendeeId])
                ? ['id' => $instructors[$w->attendeeId]->id, 'name' => $instructors[$w->attendeeId]->name, 'lastname' => $instructors[$w->attendeeId]->lastname]
                : null,
            'partners' => $partners[$w->id] ?? [],
        ], $workshops);
    }

    /**
     * @param list<Workshop> $workshops
     * @return list<array<string, mixed>> CMS: everything, including scheduling fields.
     */
    public function forCms(array $workshops): array
    {
        $instructors = $this->instructorsOf($workshops);

        return array_map(fn (Workshop $w): array => $this->base($w) + [
            'status' => $w->status->value,
            'tickets' => $w->tickets,
            'part' => $w->part,
            'relation' => $w->relation,
            'instructor' => isset($instructors[$w->attendeeId])
                ? ['id' => $instructors[$w->attendeeId]->id, 'name' => $instructors[$w->attendeeId]->name, 'lastname' => $instructors[$w->attendeeId]->lastname, 'email' => $instructors[$w->attendeeId]->email]
                : null,
        ], $workshops);
    }

    /** @return array<string, mixed> */
    private function base(Workshop $w): array
    {
        return [
            'id' => $w->id,
            'name' => $w->name,
            'public' => $w->audience,
            'level' => $w->level,
            'duration' => $w->duration,
            'observations' => $w->observations,
            'image' => $this->images->url($w->image),
        ];
    }

    /** @param list<Workshop> $workshops @return array<int, list<array<string, mixed>>> */
    private function partnersOf(array $workshops): array
    {
        $byWorkshop = $this->partners->activeByWorkshop(array_map(static fn (Workshop $w): int => $w->id, $workshops));

        return array_map(fn (array $list): array => array_map($this->partnerView->toArray(...), $list), $byWorkshop);
    }

    /** @param list<Workshop> $workshops @return array<int, Attendee> */
    private function instructorsOf(array $workshops): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn (Workshop $w): ?int => $w->attendeeId, $workshops))));

        return $this->attendees->findByIds($ids);
    }
}
