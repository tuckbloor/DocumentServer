<?php

namespace App\Support\StandaloneDav;

use Illuminate\Http\Request as LaravelRequest;
use Sabre\HTTP\Request as SabreRequest;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;

/** Bridges Laravel/Symfony requests to Sabre without printing PHP headers or body directly. */
final class StandaloneDavSapi extends Sapi
{
    private static ?SabreRequest $incoming = null;

    private static ?ResponseInterface $outgoing = null;

    public static function prepare(LaravelRequest $request): void
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        // Some FastCGI installations strip Authorization. Server configuration must forward it.
        $incoming = new SabreRequest($request->method(), $request->getRequestUri(), $headers);
        $incoming->setBody($request->getContent(true));
        self::$incoming = $incoming;
        self::$outgoing = null;
    }

    public static function getRequest(): SabreRequest
    {
        return self::$incoming ?? parent::getRequest();
    }

    public static function sendResponse(ResponseInterface $response)
    {
        self::$outgoing = $response;
    }

    public static function takeResponse(): ?ResponseInterface
    {
        $response = self::$outgoing;
        self::$outgoing = null;
        self::$incoming = null;

        return $response;
    }
}
