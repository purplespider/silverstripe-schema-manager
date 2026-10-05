<?php

namespace DorsetDigital\SchemaManager\Tests\Control;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class SchemaRegistryTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testEntityCanBeAdded(): void
    {
        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@type' => 'Thing',
            'name' => 'Example',
        ]);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('Thing', $graph[0]['@type']);
        $this->assertSame('Example', $graph[0]['name']);
    }

    public function testTypedSchemaCanBeAdded(): void
    {
        $schema = new class([
            '@type' => 'Thing',
            '@id' => 'https://example.com/#thing',
            'name' => 'Typed example',
        ]) extends Schema {
        };

        SchemaRegistry::add($schema);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('Typed example', $graph[0]['name']);
    }

    public function testEntityDataIsMergedById(): void
    {
        $id = 'https://example.com/#organisation';

        SchemaRegistry::addEntity($id, [
            '@type' => 'Organization',
            'name' => 'Example Ltd',
        ]);

        SchemaRegistry::addEntity($id, [
            'url' => 'https://example.com/',
        ]);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('Example Ltd', $graph[0]['name']);
        $this->assertSame('https://example.com/', $graph[0]['url']);
    }

    public function testDeprecatedFaqHelperStillRegistersFaqPage(): void
    {
        $this->expectUserDeprecationMessage(
            'DorsetDigital\\SchemaManager\\Control\\SchemaRegistry::addFAQ() is deprecated. '
            . 'Use FAQPageSchema::create() and SchemaRegistry::add() instead.'
        );

        SchemaRegistry::addFAQ(
            'Question one?',
            'Answer one.',
            'https://example.com/faqs/'
        );

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('FAQPage', $graph[0]['@type']);
        $this->assertSame('https://example.com/faqs/#faq', $graph[0]['@id']);
        $this->assertSame('Question one?', $graph[0]['mainEntity'][0]['name']);
    }

    public function testSilverstripeBreadcrumbsCanBeAdded(): void
    {
        $section = SiteTree::create([
            'Title' => 'About us',
            'MenuTitle' => 'About',
            'URLSegment' => 'about',
        ]);
        $section->write();

        $page = SiteTree::create([
            'Title' => 'Our team',
            'URLSegment' => 'team',
            'ParentID' => $section->ID,
        ]);
        $page->write();

        SchemaRegistry::addBreadCrumbs($page);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('BreadcrumbList', $graph[0]['@type']);
        $this->assertStringEndsWith('/about/team/#breadcrumb', $graph[0]['@id']);
        $this->assertCount(2, $graph[0]['itemListElement']);
        $this->assertSame(1, $graph[0]['itemListElement'][0]['position']);
        $this->assertSame('About', $graph[0]['itemListElement'][0]['name']);
        $this->assertSame($section->AbsoluteLink(), $graph[0]['itemListElement'][0]['item']);
        $this->assertSame(2, $graph[0]['itemListElement'][1]['position']);
        $this->assertSame('Our team', $graph[0]['itemListElement'][1]['name']);
        $this->assertSame($page->AbsoluteLink(), $graph[0]['itemListElement'][1]['item']);
    }

    public function testSingleBreadcrumbIsNotAdded(): void
    {
        $page = SiteTree::create([
            'Title' => 'About us',
            'URLSegment' => 'about',
        ]);
        $page->write();

        SchemaRegistry::addBreadCrumbs($page);

        $this->assertSame([], SchemaRegistry::getGraph());
    }

    public function testJsonContainsSchemaGraph(): void
    {
        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@type' => 'Thing',
        ]);

        $decoded = json_decode(SchemaRegistry::getJSON(), true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('Thing', $decoded['@graph'][0]['@type']);
    }
}
