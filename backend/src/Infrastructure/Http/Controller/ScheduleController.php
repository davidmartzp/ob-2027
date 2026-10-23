<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Schedule\ManageSchedule;
use App\Application\Schedule\PublicSchedule;
use App\Application\Schedule\WorkshopEnrolment;
use App\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ScheduleController
{
    public function __construct(
        private ManageSchedule $manage,
        private PublicSchedule $public,
        private WorkshopEnrolment $enrolment,
    ) {
    }

    public function rooms(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->rooms());
    }

    public function activities(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->activities());
    }

    public function events(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->events());
    }

    public function createEvent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->createEvent((array) $request->getParsedBody()), 201);
    }

    /** @param array{id: string} $args */
    public function deleteEvent(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->manage->deleteEvent((int) $args['id']);

        return Json::noContent($response);
    }

    public function workshopEvents(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->workshopEvents());
    }

    /** @param array{id: string} $args */
    public function eventAttendees(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->manage->eventAttendees((int) $args['id']));
    }

    public function activeEvents(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->public->activeEvents());
    }

    public function schedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->public->schedule());
    }

    /** @param array{id: string} $args */
    public function myEvents(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->enrolment->mine((int) $args['id']));
    }

    /** @param array{id: string} $args */
    public function enrol(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->enrolment->enrol((int) $args['id'], (array) $request->getParsedBody()), 201);
    }

    /** @param array{id: string, event: string} $args */
    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->enrolment->cancel((int) $args['id'], (int) $args['event']);

        return Json::noContent($response);
    }
}
