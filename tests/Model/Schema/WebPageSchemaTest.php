<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class WebPageSchemaTest extends SapphireTest
{
    private string $timezone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);

        parent::tearDown();
    }

    public function testDatesAreIso8601(): void
    {
        $data = WebPageSchema::fromPage(SiteTree::create([
            'Title' => 'About us',
            'URLSegment' => 'about',
            'Created' => '2018-04-19 13:51:49',
            'LastEdited' => '2020-01-15 16:04:04',
        ]))->toArray();

        $this->assertSame('2018-04-19T13:51:49+01:00', $data['datePublished']);
        $this->assertSame('2020-01-15T16:04:04+00:00', $data['dateModified']);
    }
}
