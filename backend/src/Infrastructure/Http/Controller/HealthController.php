<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Json;
use App\Infrastructure\Persistence\DatabaseHealth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class HealthController
{
    public function __construct(private DatabaseHealth $health)
    {
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->health->check();

        return Json::respond($response, $result, $result['status'] === 'ok' ? 200 : 503);
    }
}
