<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface;

/** JSON responses in UTF-8, without escaping accents or slashes. */
final class Json
{
    public static function respond(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public static function noContent(ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(204);
    }
}
