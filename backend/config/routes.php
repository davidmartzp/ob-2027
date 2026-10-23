<?php

declare(strict_types=1);

/*
 * Every route of the API, in one place. Each points to a controller method in
 * src/Infrastructure/Http/Controller; the guard says who may call it:
 *   (none) public · cms() CMS token. Attendees never sign in (decision of 2026-10-23,
 *   design.md D5): their routes are public, identified by email or id as in the legacy site.
 *
 * Route closures/groups must not be `static`: Slim binds them to the container.
 */

use App\Infrastructure\Http\Controller\AttendeeController;
use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\Http\Controller\EmailController;
use App\Infrastructure\Http\Controller\HealthController;
use App\Infrastructure\Http\Controller\ImageController;
use App\Infrastructure\Http\Controller\PartnerController;
use App\Infrastructure\Http\Controller\ScheduleController;
use App\Infrastructure\Http\Controller\WorkshopController;
use App\Infrastructure\Http\Guards;
use Slim\App;

return static function (App $app): void {
    $guards = $app->getContainer()->get(Guards::class);

    $app->get('/health', [HealthController::class, 'show']);
    $app->get('/images/{file}', [ImageController::class, 'show']);

    // Auth (spec backend/autenticacion)
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->post('/auth/logout', [AuthController::class, 'logout'])->add($guards->cms());
    $app->get('/auth/me', [AuthController::class, 'me'])->add($guards->cms());

    // Attendees (spec backend/attendees)
    $app->post('/attendees', [AttendeeController::class, 'register']);
    $app->post('/attendees/verify-code', [AttendeeController::class, 'verifyCode']);
    $app->post('/attendees/remind-code', [AttendeeController::class, 'remindCode']);
    $app->get('/attendees', [AttendeeController::class, 'index'])->add($guards->cms());
    $app->post('/attendees/confirm', [AttendeeController::class, 'confirm'])->add($guards->cms());
    $app->post('/attendees/codes', [AttendeeController::class, 'assignCodes'])->add($guards->cms());
    $app->post('/attendees/codes/regenerate', [AttendeeController::class, 'regenerateCodes'])->add($guards->cms());
    $app->get('/attendees/{id:[0-9]+}', [AttendeeController::class, 'show'])->add($guards->cms());
    $app->put('/attendees/{id:[0-9]+}', [AttendeeController::class, 'update'])->add($guards->cms());
    $app->delete('/attendees/{id:[0-9]+}', [AttendeeController::class, 'delete'])->add($guards->cms());

    // Workshops (spec backend/talleres)
    $app->get('/workshops', [WorkshopController::class, 'publicList']);
    $app->post('/workshops/by-email', [WorkshopController::class, 'byEmail']);
    $app->post('/attendees/{id:[0-9]+}/workshops', [WorkshopController::class, 'propose']);
    $app->get('/attendees/{id:[0-9]+}/workshops', [WorkshopController::class, 'listMine']);
    $app->put('/workshops/{id:[0-9]+}', [WorkshopController::class, 'update']);
    $app->delete('/workshops/{id:[0-9]+}', [WorkshopController::class, 'withdraw']);
    $app->post('/workshops/{id:[0-9]+}/image', [WorkshopController::class, 'uploadImage']);
    $app->get('/cms/workshops', [WorkshopController::class, 'cmsList'])->add($guards->cms());
    $app->patch('/workshops/{id:[0-9]+}', [WorkshopController::class, 'patch'])->add($guards->cms());

    // Partners (spec backend/partners)
    $app->get('/partners', [PartnerController::class, 'index']);
    $app->post('/partners', [PartnerController::class, 'create'])->add($guards->cms());
    $app->put('/partners/{id:[0-9]+}', [PartnerController::class, 'update'])->add($guards->cms());
    $app->delete('/partners/{id:[0-9]+}', [PartnerController::class, 'deactivate'])->add($guards->cms());
    $app->post('/partners/{id:[0-9]+}/logo', [PartnerController::class, 'uploadLogo'])->add($guards->cms());
    $app->get('/workshops/{id:[0-9]+}/partners', [PartnerController::class, 'ofWorkshop']);
    $app->put('/workshops/{id:[0-9]+}/partners', [PartnerController::class, 'replaceForWorkshop']);

    // Schedule (spec backend/agenda)
    $app->get('/rooms', [ScheduleController::class, 'rooms'])->add($guards->cms());
    $app->get('/activities', [ScheduleController::class, 'activities'])->add($guards->cms());
    $app->get('/events', [ScheduleController::class, 'events'])->add($guards->cms());
    $app->post('/events', [ScheduleController::class, 'createEvent'])->add($guards->cms());
    $app->delete('/events/{id:[0-9]+}', [ScheduleController::class, 'deleteEvent'])->add($guards->cms());
    $app->get('/events/workshops', [ScheduleController::class, 'workshopEvents'])->add($guards->cms());
    $app->get('/events/{id:[0-9]+}/attendees', [ScheduleController::class, 'eventAttendees'])->add($guards->cms());
    $app->get('/events/active', [ScheduleController::class, 'activeEvents']);
    $app->get('/events/schedule', [ScheduleController::class, 'schedule']);
    $app->get('/attendees/{id:[0-9]+}/events', [ScheduleController::class, 'myEvents']);
    $app->post('/attendees/{id:[0-9]+}/events', [ScheduleController::class, 'enrol']);
    $app->delete('/attendees/{id:[0-9]+}/events/{event:[0-9]+}', [ScheduleController::class, 'cancel']);

    // Emails sent by hand from the CMS (spec backend/correo)
    $app->get('/emails/log', [EmailController::class, 'log'])->add($guards->cms());
    $app->get('/emails/{type}/recipients', [EmailController::class, 'recipients'])->add($guards->cms());
    $app->post('/emails/{type}', [EmailController::class, 'send'])->add($guards->cms());
};
