<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Legacy;

use App\Domain\Shared\Exception\ValidationException;
use App\Infrastructure\Storage\LocalImageStorage;
use Illuminate\Database\Connection;

/**
 * Finishes moving a copy of the production database (already converted by the
 * `migrate_from_angular_ob` migration): checks the schema the backend relies on,
 * reports broken references and copies the referenced images and logos, re-checking
 * each one (the legacy upload accepted any file).
 */
final readonly class LegacyImport
{
    /** Columns the backend reads or writes, per table (production shape, 2026-10-23). */
    public const array EXPECTED_COLUMNS = [
        'users' => ['id', 'name', 'email', 'password', 'created_at', 'updated_at'],
        'attendees' => ['id', 'code', 'name', 'lastname', 'age', 'country', 'city', 'email', 'phone', 'companion', 'idname', 'paydate', 'paymethod', 'receipt', 'origamigroup', 'info', 'cardsgroup', 'created_at', 'updated_at', 'status', 'cards', 'days', 'staff', 'special_code', 'go_to_chiva', 'printCertificate', 'chiva_companions', 'pay_amount'],
        'workshops' => ['id', 'name', 'public', 'level', 'observations', 'status', 'created_at', 'updated_at', 'id_attendee', 'image', 'tickets', 'duration', 'part', 'relation'],
        'rooms' => ['id', 'name'],
        'activities' => ['id', 'name', 'description', 'duration'],
        'events' => ['id', 'id_room', 'start_time', 'end_time', 'day', 'id_workshop', 'id_activity', 'created_at', 'updated_at'],
        'events_attendees' => ['id_attendee', 'id_event'],
        'partners' => ['id', 'name', 'logo', 'whatsapp', 'message', 'status', 'created_at', 'updated_at'],
        'workshop_partner' => ['id', 'id_workshop', 'id_partner'],
    ];

    public function __construct(private Connection $db, private LocalImageStorage $images)
    {
    }

    /** @return array{missing: array<string, list<string>>, extra: array<string, list<string>>} */
    public function checkSchema(): array
    {
        $schema = $this->db->getSchemaBuilder();
        $missing = [];
        $extra = [];
        foreach (self::EXPECTED_COLUMNS as $table => $expected) {
            if (!$schema->hasTable($table)) {
                $missing[$table] = ['(tabla completa)'];
                continue;
            }
            $actual = $schema->getColumnListing($table);
            if ($diff = array_values(array_diff($expected, $actual))) {
                $missing[$table] = $diff;
            }
            if ($diff = array_values(array_diff($actual, $expected, ['email_verified_at', 'remember_token']))) {
                $extra[$table] = $diff;
            }
        }

        return ['missing' => $missing, 'extra' => $extra];
    }

    /** @return array<string, int> Rows that point to something that does not exist. */
    public function brokenReferences(): array
    {
        $count = fn (string $sql): int => (int) $this->db->selectOne($sql)->total;

        return [
            'talleres sin tallerista existente' => $count('SELECT COUNT(*) AS total FROM workshops w LEFT JOIN attendees a ON a.id = w.id_attendee WHERE w.id_attendee IS NOT NULL AND a.id IS NULL'),
            'eventos con taller inexistente' => $count('SELECT COUNT(*) AS total FROM events e LEFT JOIN workshops w ON w.id = e.id_workshop WHERE e.id_workshop IS NOT NULL AND w.id IS NULL'),
            'eventos con salón inexistente' => $count('SELECT COUNT(*) AS total FROM events e LEFT JOIN rooms r ON r.id = e.id_room WHERE r.id IS NULL'),
            'inscripciones a eventos inexistentes' => $count('SELECT COUNT(*) AS total FROM events_attendees ea LEFT JOIN events e ON e.id = ea.id_event WHERE e.id IS NULL'),
            'inscripciones de asistentes inexistentes' => $count('SELECT COUNT(*) AS total FROM events_attendees ea LEFT JOIN attendees a ON a.id = ea.id_attendee WHERE a.id IS NULL'),
        ];
    }

    /**
     * Copies every image referenced by `workshops.image` and `partners.logo` from the legacy
     * folder (app-ob/storage/app/public/images). Idempotent: already copied files are skipped.
     *
     * @return array{copied: list<string>, existing: list<string>, missing: list<string>, rejected: array<string, string>}
     */
    public function importImages(string $legacyDirectory): array
    {
        $names = array_values(array_unique(array_filter(array_merge(
            $this->db->table('workshops')->whereNotNull('image')->pluck('image')->all(),
            $this->db->table('partners')->whereNotNull('logo')->pluck('logo')->all(),
        ), static fn (mixed $name): bool => is_string($name) && $name !== '')));
        sort($names);

        $report = ['copied' => [], 'existing' => [], 'missing' => [], 'rejected' => []];
        foreach ($names as $name) {
            $source = $legacyDirectory . '/' . basename($name);
            $target = $this->images->path($name);
            if ($target !== null && is_file($target)) {
                $report['existing'][] = $name;
                continue;
            }
            if (!is_file($source)) {
                $report['missing'][] = $name;
                continue;
            }
            try {
                $this->images->importAs($name, (string) file_get_contents($source));
                $report['copied'][] = $name;
            } catch (ValidationException $error) {
                $report['rejected'][$name] = implode(' ', $error->fields);
            }
        }

        return $report;
    }
}
