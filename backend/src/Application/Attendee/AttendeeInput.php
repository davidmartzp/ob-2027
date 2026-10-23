<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Application\Shared\Validator;

/**
 * Validates the registration JSON sent by the site (`EnvioInscripcion` in
 * frontend/src/app/core/registro.ts) and maps it to `attendees` columns. Lengths follow
 * the legacy columns (e.g. paymethod is VARCHAR(20)).
 */
final class AttendeeInput
{
    private const array REGISTRATION_RULES = [
        'name' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'age' => 'required|int:1,120',
        'country' => 'string|max:255',
        'city' => 'string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|string|max:50',
        'companion' => 'string|max:255',
        'idname' => 'required|string|max:255',
        'paymethod' => 'required|string|max:20',
        'paydate' => 'date',
        'receipt' => 'string|max:255',
        'origamigroup' => 'string|max:200',
        'othermedium' => 'string|max:255',
        'info' => 'string|max:255',
        'cards' => 'bool',
        'cardgroup' => 'int:0,1000000',
        'days' => 'required|in:T,S,D',
        'printCertificate' => 'bool',
        'go_to_chiva' => 'bool',
        'chiva_companions' => 'int:0,50',
    ];

    /** Fields only the CMS may change. */
    private const array CMS_RULES = [
        'code' => 'int:1,1000000',
        'special_code' => 'string|max:3',
        'staff' => 'bool',
        'status' => 'in:0,1,5',
        'pay_amount' => 'string|max:25',
        'cardsgroup' => 'int:0,1000000',
    ];

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed> Column values.
     */
    public static function forRegistration(array $input, string $now): array
    {
        $data = Validator::validate($input, self::REGISTRATION_RULES);

        return self::columns($data) + ['paydate' => $data['paydate'] ?? $now];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed> Column values.
     */
    public static function forCmsUpdate(array $input): array
    {
        $data = Validator::validate($input, self::cmsRegistrationRules() + self::CMS_RULES);
        $columns = self::columns($data) + [
            'code' => $data['code'],
            'special_code' => $data['special_code'],
            'staff' => (bool) $data['staff'],
            'pay_amount' => $data['pay_amount'] ?? '0',
        ];
        if ($data['paydate'] !== null) {
            $columns['paydate'] = $data['paydate'];
        }
        if ($data['status'] !== null) {
            $columns['status'] = (int) $data['status'];
        }
        // The CMS sends `cardsgroup` (column name); the site form sends `cardgroup`.
        $columns['cardsgroup'] = $data['cardsgroup'] ?? $data['cardgroup'];

        return $columns;
    }

    /**
     * The CMS must be able to save legacy rows that lack a document, phone or days, so only
     * the identity fields stay required there; every other rule (format, length) still applies.
     *
     * @return array<string, string>
     */
    private static function cmsRegistrationRules(): array
    {
        $rules = [];
        foreach (self::REGISTRATION_RULES as $field => $rule) {
            $rules[$field] = in_array($field, ['name', 'lastname', 'age', 'email'], true)
                ? $rule
                : (string) preg_replace('/^required\|/', '', $rule);
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'lastname' => $data['lastname'],
            'age' => $data['age'],
            'country' => $data['country'],
            'city' => $data['city'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'companion' => $data['companion'],
            'idname' => $data['idname'],
            'paymethod' => $data['paymethod'],
            'receipt' => $data['receipt'],
            'origamigroup' => $data['origamigroup'],
            // Inherited: «how did you hear about us» — the free text wins over the option.
            'info' => $data['othermedium'] ?? $data['info'],
            'cards' => (bool) $data['cards'],
            'cardsgroup' => $data['cardgroup'],
            'days' => $data['days'],
            'printCertificate' => (bool) $data['printCertificate'],
            'go_to_chiva' => (bool) $data['go_to_chiva'],
            'chiva_companions' => $data['chiva_companions'] ?? 0,
        ];
    }
}
