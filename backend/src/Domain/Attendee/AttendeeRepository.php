<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/**
 * Persistence of attendees. Writes take the column values of the `attendees` table
 * (snake_case keys, as in the legacy schema), already validated by the use case.
 */
interface AttendeeRepository
{
    /** A non-deleted attendee. */
    public function findActive(int $id): ?Attendee;

    /**
     * Non-deleted attendees registered with that email (case-insensitive). Several people
     * can share an email (families), so this returns a list ordered by id.
     *
     * @return list<Attendee>
     */
    public function findActiveByEmail(string $email): array;

    /** A non-deleted attendee with that email whose code or special code is `$code`. */
    public function findActiveByEmailAndCode(string $email, string $code): ?Attendee;

    /** @return list<Attendee> Non-deleted attendees ordered by paydate, then id. */
    public function listActive(): array;

    /**
     * Attendees in the group, ordered by id; with `$ids`, only those (still within the group).
     *
     * @param list<int>|null $ids
     * @return list<Attendee>
     */
    public function findRecipients(RecipientGroup $group, ?array $ids = null): array;

    /**
     * Inserts an attendee. With `$assignCode`, it gets the next number after the highest
     * one, atomically: two concurrent registrations never get the same number.
     *
     * @param array<string, mixed> $fields
     */
    public function register(array $fields, bool $assignCode): Attendee;

    /** @param array<string, mixed> $fields */
    public function update(int $id, array $fields): Attendee;

    public function codeTakenByOther(int $code, int $exceptId): bool;

    /** @param list<int> $ids @return int How many went from pending to confirmed. */
    public function confirmPending(array $ids): int;

    /**
     * Gives consecutive numbers, after the highest assigned, to eligible attendees without
     * one, by paydate then id. With `$fromScratch`, first removes every number and starts at 1.
     *
     * @return int How many numbers were assigned.
     */
    public function assignCodes(CodePolicy $policy, bool $fromScratch): int;
}
