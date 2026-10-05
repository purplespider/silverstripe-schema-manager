<?php

namespace DorsetDigital\SchemaManager\Control\Middleware;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;

/**
 * Adds the JSON-LD graph to the <head> of the HTML page response.
 *
 * This runs once the response has been fully rendered, so schema registered
 * from templates is included. Working on the response rather than on every
 * template render keeps the JSON-LD out of other HTML rendered during the
 * request, such as emails.
 */
class SchemaMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);

        if (!$response instanceof HTTPResponse
            || $response instanceof HTTPStreamResponse
            || !str_contains((string) $response->getHeader('Content-Type'), 'text/html')
        ) {
            return $response;
        }

        $body = (string) $response->getBody();
        $headPosition = stripos($body, '</head');

        if ($headPosition === false) {
            return $response;
        }

        $json = SchemaRegistry::getJSON();

        if (!$json) {
            return $response;
        }

        $response->setBody(
            substr($body, 0, $headPosition)
            . '<script type="application/ld+json">' . $json . "</script>\n"
            . substr($body, $headPosition)
        );

        return $response;
    }
}
