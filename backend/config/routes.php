<?php

declare(strict_types=1);

/*
 * Every route of the API, in one place. Each points to a controller method in
 * src/Infrastructure/Http/Controller; the guard says who may call it:
 *   (none) public · cms() CMS token · attendee() attendee token · cmsOrAttendee() either.
 *
 * Route closures/groups must not be `static`: Slim binds them to the container.
 */

use App\Infrastructure\Http\Controller\AttendeeController;
use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\Http\Controller\EmailController;
use App\Infrastructure\Http\Controller\HealthController;
use App\Infrastructure\Http\Guards;
use Slim\App;

return static function (App $app): void {
    $guards = $app->getContainer()->get(Guards::class);

    $app->get('/health', [HealthController::class, 'show']);

    // Auth (spec backend/autenticacion)
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->post('/auth/logout', [AuthController::class, 'logout'])->add($guards->cms());
    $app->get('/auth/me', [AuthController::class, 'me'])->add($guards->cms());
    $app->post('/auth/attendee-link', [AuthController::class, 'requestAttendeeLink']);
    $app->post('/auth/redeem-link', [AuthController::class, 'redeemLink']);

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

    // Emails sent by hand from the CMS (spec backend/correo)
    $app->get('/emails/log', [EmailController::class, 'log'])->add($guards->cms());
    $app->get('/emails/{type}/recipients', [EmailController::class, 'recipients'])->add($guards->cms());
    $app->post('/emails/{type}', [EmailController::class, 'send'])->add($guards->cms());
};
