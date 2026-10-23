<?php

declare(strict_types=1);

/*
 * Slim runs middleware in reverse order of registration: the last one added is the
 * outermost. CORS and the security headers go last so they also wrap error responses.
 */

use App\Infrastructure\Config\Settings;
use App\Infrastructure\Http\ErrorHandler;
use App\Infrastructure\Http\InvalidJsonException;
use App\Infrastructure\Http\Middleware\Cors;
use App\Infrastructure\Http\Middleware\SecurityHeaders;
use Slim\App;
use Slim\Middleware\BodyParsingMiddleware;

return static function (App $app, Settings $settings): void {
    // Malformed JSON = 400 (by default Slim would silently treat it as an empty body).
    $app->add(new BodyParsingMiddleware([
        'application/json' => static function (string $input): ?array {
            if (trim($input) === '') {
                return null;
            }
            $data = json_decode($input, true);

            return is_array($data) ? $data : throw new InvalidJsonException();
        },
    ]));
    $app->addRoutingMiddleware();

    $errors = $app->addErrorMiddleware($settings->debug(), false, false);
    $errors->setDefaultErrorHandler($app->getContainer()->get(ErrorHandler::class));

    $app->add(SecurityHeaders::class);
    $app->add(Cors::class);
};
