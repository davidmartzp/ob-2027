<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Infrastructure\Mail\TransportFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

final class TransportFactoryTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function insecureDsns(): iterable
    {
        yield 'null transport' => ['null://null'];
        yield 'sendmail' => ['sendmail://default'];
        yield 'certificate not verified' => ['smtps://u:p@smtp.example.com:465?verify_peer=0'];
        yield 'STARTTLS disabled' => ['smtp://u:p@smtp.example.com:587?auto_tls=false'];
    }

    #[Test]
    #[DataProvider('insecureDsns')]
    public function production_refuses_insecure_transports(string $dsn): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insecure MAILER_DSN/');

        TransportFactory::create($dsn, production: true);
    }

    #[Test]
    public function production_accepts_implicit_tls(): void
    {
        $transport = TransportFactory::create('smtps://user:pass@host51.latinoamericahosting.com:465', production: true);

        self::assertInstanceOf(EsmtpTransport::class, $transport);
        self::assertTrue($transport->getStream()->isTLS());
    }

    #[Test]
    public function production_makes_starttls_mandatory_on_plain_smtp(): void
    {
        $transport = TransportFactory::create('smtp://user:pass@smtp.example.com:587', production: true);

        self::assertInstanceOf(EsmtpTransport::class, $transport);
        $requireTls = (new \ReflectionProperty(EsmtpTransport::class, 'requireTls'))->getValue($transport);
        self::assertTrue($requireTls, 'Without STARTTLS the connection must fail instead of sending in clear text');
    }

    #[Test]
    public function development_allows_any_transport(): void
    {
        self::assertNotNull(TransportFactory::create('null://null', production: false));
    }
}
