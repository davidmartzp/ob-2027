<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Persistence\Migration\Migrator;
use App\Infrastructure\Storage\LocalImageStorage;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\MailerInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

/**
 * Base for functional tests: boots the real app (config/bootstrap.php) on in-memory
 * SQLite with every migration applied, a FrozenClock instead of the system clock and a
 * RecordingMailer instead of the SMTP server. Uploaded images go to a temporary folder.
 */
abstract class FunctionalTestCase extends TestCase
{
    protected App $app;
    protected FrozenClock $clock;
    protected RecordingMailer $mailer;
    protected string $imageDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new FrozenClock();
        $this->mailer = new RecordingMailer();
        $this->imageDir = sys_get_temp_dir() . '/ob-test-images-' . bin2hex(random_bytes(4));
        $this->setUpApp();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->imageDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->imageDir);
        parent::tearDown();
    }

    /**
     * (Re)boots the app on a fresh database, keeping the clock and mailer. Tests that need
     * a different environment mid-test call it with the extra variables.
     *
     * @param array<string, string> $extraEnvironment
     */
    protected function setUpApp(array $extraEnvironment = []): void
    {
        $this->app = self::bootApp($extraEnvironment + $this->environment(), [
            ClockInterface::class => $this->clock,
            MailerInterface::class => $this->mailer,
            LocalImageStorage::class => new LocalImageStorage($this->imageDir, 'http://api.test'),
        ]);
        $this->app->getContainer()->get(Migrator::class)->migrate();
    }

    /** Extra environment variables for this test class. @return array<string, string> */
    protected function environment(): array
    {
        return [];
    }

    /**
     * @param array<string, string> $environment
     * @param array<string, mixed>  $definitions
     */
    protected static function bootApp(array $environment = [], array $definitions = []): App
    {
        return (require dirname(__DIR__, 2) . '/config/bootstrap.php')($environment, $definitions);
    }

    /** @template T @param class-string<T> $id @return T */
    protected function service(string $id): mixed
    {
        return $this->app->getContainer()->get($id);
    }

    protected function db(): Connection
    {
        return $this->service(Connection::class);
    }

    /**
     * @param array<string, mixed>|string|null $body    Array = JSON; string = raw body.
     * @param array<string, string>            $headers
     */
    protected function request(string $method, string $path, array|string|null $body = null, array $headers = [], string $ip = '203.0.113.10'): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path, ['REMOTE_ADDR' => $ip]);
        if ($body !== null) {
            $headers += ['Content-Type' => 'application/json'];
            $request->getBody()->write(is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body);
        }
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->app->handle($request);
    }

    /** Multipart upload of one file field. @param array<string, string> $headers */
    protected function upload(string $path, string $field, string $bytes, string $clientName, array $headers = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', $path, ['REMOTE_ADDR' => '203.0.113.10'])
            ->withUploadedFiles([$field => new UploadedFile((new StreamFactory())->createStream($bytes), $clientName, 'image/jpeg', strlen($bytes), UPLOAD_ERR_OK)]);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->app->handle($request);
    }

    /** @return array<string, string> */
    protected static function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    /** @return array<mixed> */
    protected function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}
