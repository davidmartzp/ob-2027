<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Config\Settings;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS only for the origins in `CORS_ORIGINS` (site and CMS). Answers `OPTIONS`
 * preflights without reaching the routes. No cookies (Bearer tokens), so no
 * `Allow-Credentials`.
 */
final readonly class Cors implements MiddlewareInterface
{
    private const string METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const string HEADERS = 'Authorization, Content-Type, Accept';

    public function __construct(private ResponseFactoryInterface $responses, private Settings $settings)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $isPreflight = $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');

        $response = $isPreflight ? $this->responses->createResponse(204) : $handler->handle($request);
        $response = $response->withAddedHeader('Vary', 'Origin');
        if ($origin === '' || !in_array($origin, $this->settings->list('CORS_ORIGINS'), true)) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        if ($isPreflight) {
            $response = $response
                ->withHeader('Access-Control-Allow-Methods', self::METHODS)
                ->withHeader('Access-Control-Allow-Headers', self::HEADERS)
                ->withHeader('Access-Control-Max-Age', '600');
        }

        return $response;
    }
}
