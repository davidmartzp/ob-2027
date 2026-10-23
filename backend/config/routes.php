<?php

declare(strict_types=1);

/*
 * Every route of the API, in one place. Each points to a controller method in
 * src/Infrastructure/Http/Controller; the guard says who may call it:
 *   (none) public · cms() CMS token · attendee() attendee token · cmsOrAttendee() either.
 *
 * Route closures/groups must not be `static`: Slim binds them to the container.
 */

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

    // Emails sent by hand from the CMS (spec backend/correo)
    $app->get('/emails/log', [EmailController::class, 'log'])->add($guards->cms());
    $app->get('/emails/{type}/recipients', [EmailController::class, 'recipients'])->add($guards->cms());
    $app->post('/emails/{type}', [EmailController::class, 'send'])->add($guards->cms());
};
