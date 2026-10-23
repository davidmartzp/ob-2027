<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Partner\ManagePartners;
use App\Application\Partner\WorkshopPartners;
use App\Infrastructure\Http\Json;
use App\Infrastructure\Http\RequestSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PartnerController
{
    public function __construct(private ManagePartners $partners, private WorkshopPartners $workshopPartners)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->partners->publicList());
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->partners->create((array) $request->getParsedBody()), 201);
    }

    /** @param array{id: string} $args */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->partners->update((int) $args['id'], (array) $request->getParsedBody()));
    }

    /** @param array{id: string} $args */
    public function deactivate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->partners->deactivate((int) $args['id']);

        return Json::noContent($response);
    }

    /** @param array{id: string} $args */
    public function uploadLogo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->partners->setLogo((int) $args['id'], WorkshopController::uploadedBytes($request, 'logo')));
    }

    /** @param array{id: string} $args */
    public function ofWorkshop(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->workshopPartners->list((int) $args['id']));
    }

    /** @param array{id: string} $args */
    public function replaceForWorkshop(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->workshopPartners->replace(RequestSession::of($request), (int) $args['id'], (array) $request->getParsedBody()));
    }
}
