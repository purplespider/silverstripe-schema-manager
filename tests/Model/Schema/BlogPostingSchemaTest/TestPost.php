<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema\BlogPostingSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Mirrors the Silverstripe Blog fields used by BlogPostingSchema, so the
 * tests don't need the blog module installed.
 */
class TestPost extends DataObject implements TestOnly
{
    private static string $table_name = 'SchemaManager_BlogPostingSchemaTest_TestPost';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'PublishDate' => 'Datetime',
        'Summary' => 'HTMLText',
        'MetaDescription' => 'Text',
    ];

    public function AbsoluteLink(): string
    {
        return 'https://example.com/news/example-post';
    }
}
