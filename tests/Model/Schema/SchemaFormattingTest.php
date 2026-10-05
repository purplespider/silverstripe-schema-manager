<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\Dev\SapphireTest;

class SchemaFormattingTest extends SapphireTest
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

    public function testDateTimeIsFormattedAsIso8601WithOffset(): void
    {
        $this->assertSame('2026-09-16T14:44:42+01:00', Schema::formatDateTime('2026-09-16 14:44:42'));
        $this->assertSame('2026-01-16T14:44:42+00:00', Schema::formatDateTime('2026-01-16 14:44:42'));
    }

    public function testEmptyDateTimeIsNull(): void
    {
        $this->assertNull(Schema::formatDateTime(null));
        $this->assertNull(Schema::formatDateTime(''));
    }

    public function testHtmlIsConvertedToSingleLinePlainText(): void
    {
        $this->assertSame(
            'One & two Three',
            Schema::plainText("<p>One &amp; two</p>\n<p>Three</p>")
        );
    }

    public function testMarkupOnlyHtmlIsNull(): void
    {
        $this->assertNull(Schema::plainText('<p>&nbsp;</p>'));
        $this->assertNull(Schema::plainText(''));
        $this->assertNull(Schema::plainText(null));
    }
}
