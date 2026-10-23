<?php

declare(strict_types=1);

namespace Tests\Functional\Platform;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FunctionalTestCase;

final class CorsTest extends FunctionalTestCase
{
    #[Test]
    public function allowed_origin_gets_the_header(): void
    {
        $response = $this->request('GET', '/health', headers: ['Origin' => 'http://cms.test']);

        self::assertSame('http://cms.test', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertStringContainsString('Origin', $response->getHeaderLine('Vary'));
    }

    #[Test]
    public function other_origins_do_not(): void
    {
        $response = $this->request('GET', '/health', headers: ['Origin' => 'http://evil.test']);

        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function preflight_is_answered_without_reaching_routes(): void
    {
        $response = $this->request('OPTIONS', '/attendees', headers: [
            'Origin' => 'http://site.test',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ]);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('http://site.test', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertStringContainsString('POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        self::assertStringContainsString('Authorization', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    #[Test]
    public function errors_carry_cors_too(): void
    {
        $response = $this->request('GET', '/does-not-exist', headers: ['Origin' => 'http://site.test']);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('http://site.test', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
