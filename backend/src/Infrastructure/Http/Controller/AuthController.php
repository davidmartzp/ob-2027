<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Auth\GetCurrentUser;
use App\Application\Auth\Login;
use App\Application\Auth\Logout;
use App\Application\Auth\RedeemAccessLink;
use App\Application\Auth\RequestAccessLink;
use App\Infrastructure\Http\Json;
use App\Infrastructure\Http\RequestSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** HTTP only: reads the request, calls the use case, writes JSON. */
final readonly class AuthController
{
    public function __construct(
        private Login $login,
        private Logout $logout,
        private GetCurrentUser $currentUser,
        private RequestAccessLink $requestLink,
        private RedeemAccessLink $redeemLink,
    ) {
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // REMOTE_ADDR only: anyone can forge X-Forwarded-For and dodge the per-IP limit.
        $ip = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');

        return Json::respond($response, $this->login->execute((array) $request->getParsedBody(), $ip));
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->logout->execute(RequestSession::of($request));

        return Json::noContent($response);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->currentUser->execute(RequestSession::of($request)));
    }

    public function requestAttendeeLink(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->requestLink->execute((array) $request->getParsedBody());

        return Json::respond($response, [
            'message' => 'Si ese correo está inscrito, te enviamos un enlace para entrar. Revisa también el correo no deseado.',
        ], 202);
    }

    public function redeemLink(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->redeemLink->execute((array) $request->getParsedBody()));
    }
}
