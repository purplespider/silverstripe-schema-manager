<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class PageURLTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(Director::class, 'alternate_base_url', 'https://example.com/');
    }

    public function testPageURLFollowsTrailingSlashSetting(): void
    {
        Config::modify()->set(Controller::class, 'add_trailing_slash', false);
        $this->assertSame('https://example.com/about', Schema::pageURL('https://example.com/about/'));

        Config::modify()->set(Controller::class, 'add_trailing_slash', true);
        $this->assertSame('https://example.com/about/', Schema::pageURL('https://example.com/about'));
    }

    public function testExternalURLIsUnchanged(): void
    {
        Config::modify()->set(Controller::class, 'add_trailing_slash', false);

        $this->assertSame('https://elsewhere.test/page/', Schema::pageURL('https://elsewhere.test/page/'));
    }

    public function testWebPageURLMatchesPageLink(): void
    {
        Config::modify()->set(Controller::class, 'add_trailing_slash', false);

        $page = SiteTree::create(['Title' => 'About us', 'URLSegment' => 'about']);
        $data = WebPageSchema::fromPage($page)->toArray();

        $this->assertSame('https://example.com/about', $data['url']);
        $this->assertSame($page->AbsoluteLink(), $data['url']);
        $this->assertSame('https://example.com/about/#webpage', $data['@id']);
    }

    public function testBuilderURLFollowsTrailingSlashSettingButIDDoesNot(): void
    {
        Config::modify()->set(Controller::class, 'add_trailing_slash', false);

        $data = ProductSchema::create('https://example.com/products/example/', 'Example')
            ->setOffer(10, 'GBP')
            ->toArray();

        $this->assertSame('https://example.com/products/example', $data['url']);
        $this->assertSame('https://example.com/products/example', $data['offers']['url']);
        $this->assertSame('https://example.com/products/example/#product', $data['@id']);
        $this->assertSame('https://example.com/products/example/#webpage', $data['isPartOf']['@id']);
    }
}
