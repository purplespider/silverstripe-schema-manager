<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;
use DorsetDigital\SchemaManager\Tests\Model\Schema\BlogPostingSchemaTest\TestPost;
use SilverStripe\Dev\SapphireTest;

class BlogPostingSchemaTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestPost::class,
    ];

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

    public function testHtmlSummaryIsOutputAsPlainText(): void
    {
        $data = BlogPostingSchema::fromBlogPost(TestPost::create([
            'Title' => 'Example post',
            'Summary' => '<p>An <strong>example</strong> &amp; summary.</p>',
            'MetaDescription' => 'Meta description.',
        ]))->toArray();

        $this->assertSame('An example & summary.', $data['description']);
    }

    public function testEmptySummaryFallsBackToMetaDescription(): void
    {
        $data = BlogPostingSchema::fromBlogPost(TestPost::create([
            'Title' => 'Example post',
            'Summary' => '<p>&nbsp;</p>',
            'MetaDescription' => 'Meta description.',
        ]))->toArray();

        $this->assertSame('Meta description.', $data['description']);
    }

    public function testDatesAreIso8601(): void
    {
        $data = BlogPostingSchema::fromBlogPost(TestPost::create([
            'Title' => 'Example post',
            'PublishDate' => '2025-05-02 08:40:51',
            'Created' => '2025-04-24 09:12:43',
            'LastEdited' => '2025-05-03 10:00:00',
        ]))->toArray();

        $this->assertSame('2025-05-02T08:40:51+01:00', $data['datePublished']);
        $this->assertSame('2025-05-03T10:00:00+01:00', $data['dateModified']);
    }

    public function testDatePublishedFallsBackToCreated(): void
    {
        $data = BlogPostingSchema::fromBlogPost(TestPost::create([
            'Title' => 'Example post',
            'Created' => '2025-04-24 09:12:43',
        ]))->toArray();

        $this->assertSame('2025-04-24T09:12:43+01:00', $data['datePublished']);
    }
}
