<?php

declare(strict_types=1);

namespace App\Application\Mail;

use App\Domain\Attendee\RecipientGroup;
use App\Domain\Shared\Exception\NotFoundException;

/**
 * Every email the backend sends. Templates and subjects are ported as production sends
 * them today (design.md D6c), so some subjects still say 2025: change them here.
 * Recipients reproduce the filters of the legacy send* endpoints.
 */
final class EmailCatalog
{
    /** @return array<string, EmailType> */
    public static function all(): array
    {
        $types = [
            // Sent from the CMS with POST /emails/{type}
            new EmailType('welcome', 'Te damos la bienvenida!', 'welcome', RecipientGroup::Active, resendable: true),
            new EmailType('code', 'Este es tu código de participante', 'code', RecipientGroup::WithCode, resendable: true),
            new EmailType('instructions', 'Instrucciones origami bogota 2025', 'instructions', RecipientGroup::Confirmed),
            new EmailType('book', 'Páginas de origami 2025', 'book', RecipientGroup::Confirmed),
            new EmailType('important-notice', '¡Ya casi! Origami Bogotá 2026 💬', 'important-notice', RecipientGroup::Active),
            new EmailType('general-info', '📋 Resumen e información importante - Origami Bogotá 2025 🔗', 'general-info', RecipientGroup::Active),
            new EmailType('correction', 'Importante - Corrección sobre tu número de participante', 'correction', RecipientGroup::Active, requiresIds: true),
            // Automatic only
            new EmailType('team-notice', 'Nuevo registro en Origami Bogotá 2025', 'team-notice', null),
            new EmailType('code-reminder', 'Tu número de participante en Origami Bogotá', 'code-reminder', null),
        ];

        return array_column(array_map(static fn (EmailType $t): array => [$t->key, $t], $types), 1, 0);
    }

    public static function get(string $key): EmailType
    {
        return self::all()[$key] ?? throw new NotFoundException("No existe el tipo de correo «{$key}».");
    }

    /** A type the CMS can send by hand; automatic ones answer 404 like unknown ones. */
    public static function manual(string $key): EmailType
    {
        $type = self::get($key);

        return $type->isManual() ? $type : throw new NotFoundException("No existe el tipo de correo «{$key}».");
    }
}
