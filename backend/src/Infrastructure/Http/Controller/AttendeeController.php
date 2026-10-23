<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Attendee\ManageAttendees;
use App\Application\Attendee\RegisterAttendee;
use App\Application\Attendee\RemindAttendeeCode;
use App\Application\Attendee\VerifyAttendeeCode;
use App\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AttendeeController
{
    public function __construct(
        private RegisterAttendee $register,
        private VerifyAttendeeCode $verifyCode,
        private RemindAttendeeCode $remindCode,
        private ManageAttendees $manage,
    ) {
    }

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ip = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');

        return Json::respond($response, $this->register->execute((array) $request->getParsedBody(), $ip), 201);
    }

    public function verifyCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->verifyCode->execute((array) $request->getParsedBody()));
    }

    public function remindCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->remindCode->execute((array) $request->getParsedBody());

        return Json::respond($response, [
            'message' => 'Si ese correo está inscrito, te enviamos tu número de participante. Revisa también el correo no deseado.',
        ], 202);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->list());
    }

    /** @param array{id: string} $args */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->manage->show((int) $args['id']));
    }

    /** @param array{id: string} $args */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->manage->update((int) $args['id'], (array) $request->getParsedBody()));
    }

    /** @param array{id: string} $args */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->manage->delete((int) $args['id']);

        return Json::noContent($response);
    }

    public function confirm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->confirm((array) $request->getParsedBody()));
    }

    public function assignCodes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->assignMissingCodes());
    }

    public function regenerateCodes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->regenerateCodes((array) $request->getParsedBody()));
    }
}
