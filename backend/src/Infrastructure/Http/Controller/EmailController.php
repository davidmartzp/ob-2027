<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Mail\GetEmailLog;
use App\Application\Mail\ListEmailRecipients;
use App\Application\Mail\SendEmail;
use App\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EmailController
{
    public function __construct(
        private SendEmail $send,
        private ListEmailRecipients $recipients,
        private GetEmailLog $log,
    ) {
    }

    /** @param array{type: string} $args */
    public function send(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->send->execute($args['type'], (array) $request->getParsedBody()), 202);
    }

    /** @param array{type: string} $args */
    public function recipients(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->recipients->execute($args['type']));
    }

    public function log(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->log->execute($request->getQueryParams()));
    }
}
