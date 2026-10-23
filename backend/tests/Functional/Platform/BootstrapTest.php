<?php

declare(strict_types=1);

namespace Tests\Functional\Platform;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\FunctionalTestCase;

final class BootstrapTest extends FunctionalTestCase
{
    #[Test]
    public function fails_naming_the_missing_variable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/DB_HOST/');

        self::bootApp(['DB_CONNECTION' => 'mariadb', 'DB_HOST' => '', 'DB_USERNAME' => 'origami']);
    }

    #[Test]
    public function honours_the_base_path(): void
    {
        $app = self::bootApp(['APP_BASE_PATH' => '/api/']);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');

        self::assertSame(200, $app->handle($request)->getStatusCode());
    }
}
