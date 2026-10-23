<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use RuntimeException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Builds the Symfony Mailer transport from `MAILER_DSN`. In production it refuses
 * anything that is not SMTP with TLS and certificate verification: `smtps://` (implicit
 * TLS, 465) or `smtp://` with STARTTLS made mandatory (require_tls).
 */
final class TransportFactory
{
    public static function create(string $dsn, bool $production): TransportInterface
    {
        if ($production) {
            self::ensureSecure($dsn);
        }
        $transport = Transport::fromDsn($dsn);
        if ($production && $transport instanceof EsmtpTransport && !$transport->getStream()->isTLS()) {
            $transport->setRequireTls(true);
        }

        return $transport;
    }

    private static function ensureSecure(string $dsn): void
    {
        $parsed = Dsn::fromString($dsn);
        $insecure = match (true) {
            !in_array($parsed->getScheme(), ['smtp', 'smtps'], true) => "the scheme «{$parsed->getScheme()}» is not SMTP",
            $parsed->getOption('verify_peer') !== null && !filter_var($parsed->getOption('verify_peer'), FILTER_VALIDATE_BOOL) => 'verify_peer is disabled',
            $parsed->getOption('auto_tls') !== null && !filter_var($parsed->getOption('auto_tls'), FILTER_VALIDATE_BOOL) => 'auto_tls is disabled',
            default => null,
        };
        if ($insecure !== null) {
            throw new RuntimeException("Insecure MAILER_DSN for production: {$insecure}. Use smtps://…:465 or smtp://…:587 with TLS.");
        }
    }
}
