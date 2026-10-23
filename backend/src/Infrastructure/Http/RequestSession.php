<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Auth\Session;
use App\Domain\Shared\Exception\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;

/** Where the authenticated Session travels inside a request. */
final class RequestSession
{
    private const string ATTRIBUTE = 'session';

    public static function attach(ServerRequestInterface $request, Session $session): ServerRequestInterface
    {
        return $request->withAttribute(self::ATTRIBUTE, $session);
    }

    public static function of(ServerRequestInterface $request): Session
    {
        $session = $request->getAttribute(self::ATTRIBUTE);

        return $session instanceof Session ? $session : throw new UnauthorizedException();
    }
}
