<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Domain\Shared\Exception\NotFoundException;
use App\Infrastructure\Storage\LocalImageStorage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Stream;

/** GET /images/{file}: serves stored images with a fixed image Content-Type (never as code). */
final readonly class ImageController
{
    public function __construct(private LocalImageStorage $storage)
    {
    }

    /** @param array{file: string} $args */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = $this->storage->path($args['file']);
        if ($path === null || !is_file($path)) {
            throw new NotFoundException('No existe esa imagen.');
        }

        return $response
            ->withBody(new Stream(fopen($path, 'rb')))
            ->withHeader('Content-Type', LocalImageStorage::mimeOf($path))
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withHeader('Cross-Origin-Resource-Policy', 'cross-origin');
    }
}
