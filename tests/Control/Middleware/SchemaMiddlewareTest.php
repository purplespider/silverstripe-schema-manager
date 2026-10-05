<?php

namespace DorsetDigital\SchemaManager\Tests\Control\Middleware;

use DorsetDigital\SchemaManager\Control\Middleware\SchemaMiddleware;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;

class SchemaMiddlewareTest extends SapphireTest
{
    private const PAGE = '<html><head><title>Page</title></head><body></body></html>';

    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testSchemaIsAddedToHeadOfHtmlResponse(): void
    {
        $body = $this->process(function (): HTTPResponse {
            // Registered while the response is rendered, e.g. from a template
            $this->addThing();

            return HTTPResponse::create(self::PAGE)->addHeader('Content-Type', 'text/html; charset=utf-8');
        })->getBody();

        $this->assertMatchesRegularExpression(
            '#<title>Page</title><script type="application/ld\+json">.*Example thing.*</script>\s*</head>#s',
            $body
        );
    }

    public function testNonHtmlResponseIsUnchanged(): void
    {
        $this->addThing();

        $body = $this->process(
            fn (): HTTPResponse => HTTPResponse::create(self::PAGE)->addHeader('Content-Type', 'application/json')
        )->getBody();

        $this->assertSame(self::PAGE, $body);
    }

    public function testHtmlFragmentIsUnchanged(): void
    {
        $this->addThing();

        $body = $this->process(
            fn (): HTTPResponse => HTTPResponse::create('<p>Fragment</p>')->addHeader('Content-Type', 'text/html')
        )->getBody();

        $this->assertSame('<p>Fragment</p>', $body);
    }

    public function testResponseIsUnchangedWithoutSchema(): void
    {
        $body = $this->process(
            fn (): HTTPResponse => HTTPResponse::create(self::PAGE)->addHeader('Content-Type', 'text/html')
        )->getBody();

        $this->assertSame(self::PAGE, $body);
    }

    public function testOtherHtmlRenderedDuringRequestIsUnchanged(): void
    {
        $this->addThing();

        // Email and other template renders go through Requirements
        $html = Requirements::includeInHTML(self::PAGE);

        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    private function process(callable $delegate): HTTPResponse
    {
        return (new SchemaMiddleware())->process(new HTTPRequest('GET', '/'), $delegate);
    }

    private function addThing(): void
    {
        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@type' => 'Thing',
            '@id' => 'https://example.com/#thing',
            'name' => 'Example thing',
        ]);
    }
}
