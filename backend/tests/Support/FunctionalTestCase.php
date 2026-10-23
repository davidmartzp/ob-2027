<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Persistence\Migration\Migrator;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Base for functional tests: boots the real app (config/bootstrap.php) on in-memory
 * SQLite with every migration applied, and a FrozenClock instead of the system clock.
 */
abstract class FunctionalTestCase extends TestCase
{
    protected App $app;
    protected FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new FrozenClock();
        $this->app = self::bootApp($this->environment(), [ClockInterface::class => $this->clock]);
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
