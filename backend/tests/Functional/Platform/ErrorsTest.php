<?php

declare(strict_types=1);

namespace Tests\Functional\Platform;

use App\Application\Shared\Validator;
use App\Infrastructure\Http\Json;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Tests\Support\FunctionalTestCase;

final class ErrorsTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Test-only routes that trigger each kind of error (Slim does not accept static closures).
        $this->app->post('/test/validate', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            $data = Validator::validate((array) $request->getParsedBody(), [
                'name' => 'required|string|max:10',
                'email' => 'required|email',
                'age' => 'required|int:1,120',
                'days' => 'required|in:T,S,D',
            ]);

            return Json::respond($response, $data);
        });
        $this->app->get('/test/explode', function (): never {
            throw new RuntimeException('SELECT * FROM secret WHERE password = 123');
        });
    }

    #[Test]
    public function unknown_route_is_a_json_404(): void
    {
        $response = $this->request('GET', '/does-not-exist');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('not_found', $this->json($response)['error']['code']);
    }

    #[Test]
    public function wrong_method_is_405(): void
    {
        $response = $this->request('DELETE', '/health');

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('method_not_allowed', $this->json($response)['error']['code']);
    }

    #[Test]
    public function failed_validation_lists_every_field(): void
    {
        $response = $this->request('POST', '/test/validate', ['name' => '', 'email' => 'pepito', 'age' => '200', 'days' => 'X']);

        self::assertSame(422, $response->getStatusCode());
        $error = $this->json($response)['error'];
        self::assertSame('validation_failed', $error['code']);
        self::assertSame(['name', 'email', 'age', 'days'], array_keys($error['fields']));
        self::assertSame('Escriba un correo válido.', $error['fields']['email']);
    }

    #[Test]
    public function valid_input_comes_back_cast(): void
    {
        $response = $this->request('POST', '/test/validate', ['name' => ' Ana ', 'email' => 'Ana@Ejemplo.COM', 'age' => '30', 'days' => 'T', 'extra' => 'x']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['name' => 'Ana', 'email' => 'ana@ejemplo.com', 'age' => 30, 'days' => 'T'], $this->json($response));
    }

    #[Test]
    public function malformed_json_is_400(): void
    {
        $response = $this->request('POST', '/test/validate', '{"name": ');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('bad_request', $this->json($response)['error']['code']);
    }

    #[Test]
    public function unexpected_error_hides_internals_in_production(): void
    {
        $log = ini_set('error_log', '/dev/null');
        $response = $this->request('GET', '/test/explode');
        ini_set('error_log', (string) $log);

        self::assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        self::assertSame('internal_error', $this->json($response)['error']['code']);
        self::assertStringNotContainsString('secret', $body);
        self::assertStringNotContainsString('.php', $body);
        self::assertArrayNotHasKey('debug', $this->json($response)['error']);
    }

    #[Test]
    public function every_response_has_security_headers(): void
    {
        $response = $this->request('GET', '/does-not-exist');

        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
    }
}
