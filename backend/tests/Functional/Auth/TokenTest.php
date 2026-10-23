<?php

declare(strict_types=1);

namespace Tests\Functional\Auth;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use App\Infrastructure\Http\Guards;
use App\Infrastructure\Http\Json;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\FunctionalTestCase;

final class TokenTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $guards = $this->service(Guards::class);
        // Test-only route behind the CMS guard.
        $this->app->get('/test/cms', fn (ServerRequestInterface $q, ResponseInterface $r) => Json::respond($r, ['ok' => true]))
            ->add($guards->cms());
    }

    private function token(TokenType $type, int $id): string
    {
        return $this->service(TokenService::class)->issue($type, $id)['token'];
    }

    #[Test]
    public function private_route_without_token_is_401(): void
    {
        $response = $this->request('GET', '/test/cms');

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('unauthorized', $this->json($response)['error']['code']);
    }

    #[Test]
    public function made_up_token_is_401(): void
    {
        self::assertSame(401, $this->request('GET', '/test/cms', headers: self::bearer(str_repeat('a', 64)))->getStatusCode());
        self::assertSame(401, $this->request('GET', '/test/cms', headers: ['Authorization' => 'Basic dXNlcjpwYXNz'])->getStatusCode());
    }

    #[Test]
    public function expired_cms_token_is_401(): void
    {
        $this->clock->set('2027-06-05 08:00');
        $token = $this->token(TokenType::Cms, 1);
        self::assertSame(200, $this->request('GET', '/test/cms', headers: self::bearer($token))->getStatusCode());

        $this->clock->advance('+12 hours +1 minute');
        self::assertSame(401, $this->request('GET', '/test/cms', headers: self::bearer($token))->getStatusCode());
    }
}
