<?php

namespace DorsetDigital\SchemaManager\Tests\Control;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\BreadcrumbListSchema;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use PHPUnit\Framework\TestCase;

class SchemaRegistryRelationshipsTest extends TestCase
{
    private const PAGE_URL = 'https://example.com/page/';

    private const WEBPAGE_ID = self::PAGE_URL . '#webpage';

    protected function setUp(): void
    {
        parent::setUp();

        SchemaRegistry::flush();
        SchemaRegistry::add(new WebPageSchema([
            '@type' => 'WebPage',
            '@id' => self::WEBPAGE_ID,
        ]));
    }

    protected function tearDown(): void
    {
        SchemaRegistry::flush();

        parent::tearDown();
    }

    public function testChangesToTypedSchemaAfterRegistrationAreIncluded(): void
    {
        $product = ProductSchema::create(self::PAGE_URL, 'Old name');
        SchemaRegistry::add($product);

        $product->update(['name' => 'New name']);

        $this->assertSame('New name', $this->entity(self::PAGE_URL . '#product')['name']);
    }

    public function testRawEntityAugmentsTypedSchema(): void
    {
        SchemaRegistry::add(ProductSchema::create(self::PAGE_URL, 'Typed name'));
        SchemaRegistry::addEntity(self::PAGE_URL . '#product', [
            'name' => 'Augmented name',
            'sku' => 'SKU-123',
        ]);

        $product = $this->entity(self::PAGE_URL . '#product');

        $this->assertSame('Augmented name', $product['name']);
        $this->assertSame('SKU-123', $product['sku']);
        $this->assertSame('Product', $product['@type']);
    }

    public function testMainEntityIsLinkedFromWebPage(): void
    {
        SchemaRegistry::add(
            ProductSchema::create(self::PAGE_URL, 'Widget')->setMainEntityOfPage(self::PAGE_URL)
        );

        $this->assertSame(
            ['@id' => self::PAGE_URL . '#product'],
            $this->entity(self::WEBPAGE_ID)['mainEntity']
        );
    }

    public function testEarlierMainEntityClaimIsDemotedToPartOfPage(): void
    {
        SchemaRegistry::add(
            ProductSchema::create(self::PAGE_URL, 'Widget')->setMainEntityOfPage(self::PAGE_URL)
        );
        SchemaRegistry::add(
            FAQPageSchema::create(self::PAGE_URL)->addQuestion('Q', 'A')->setMainEntityOfPage(self::PAGE_URL)
        );

        $product = $this->entity(self::PAGE_URL . '#product');

        $this->assertSame(['@id' => self::PAGE_URL . '#faq'], $this->entity(self::WEBPAGE_ID)['mainEntity']);
        $this->assertSame(['@id' => self::WEBPAGE_ID], $product['isPartOf']);
        $this->assertArrayNotHasKey('mainEntityOfPage', $product);
    }

    public function testGraphReflectsRegistrationsMadeAfterEarlierRender(): void
    {
        SchemaRegistry::add(
            ProductSchema::create(self::PAGE_URL, 'Widget')->setMainEntityOfPage(self::PAGE_URL)
        );

        // e.g. an email rendered part way through the request
        SchemaRegistry::getJSON();

        SchemaRegistry::add(
            FAQPageSchema::create(self::PAGE_URL)->addQuestion('Q', 'A')->setMainEntityOfPage(self::PAGE_URL)
        );

        $this->assertSame(['@id' => self::PAGE_URL . '#faq'], $this->entity(self::WEBPAGE_ID)['mainEntity']);
        $this->assertArrayNotHasKey('mainEntityOfPage', $this->entity(self::PAGE_URL . '#product'));
    }

    public function testBuildingGraphDoesNotChangeRegisteredSchemas(): void
    {
        $webPage = new WebPageSchema(['@type' => 'WebPage', '@id' => self::WEBPAGE_ID]);
        SchemaRegistry::flush();
        SchemaRegistry::add($webPage);
        SchemaRegistry::add(
            ProductSchema::create(self::PAGE_URL, 'Widget')->setMainEntityOfPage(self::PAGE_URL)
        );

        SchemaRegistry::getGraph();

        $this->assertArrayNotHasKey('mainEntity', $webPage->toArray());
    }

    public function testTypedBreadcrumbIsLinkedFromWebPage(): void
    {
        SchemaRegistry::add(new BreadcrumbListSchema([
            '@type' => 'BreadcrumbList',
            '@id' => self::PAGE_URL . '#breadcrumb',
            'itemListElement' => [],
        ]));

        $this->assertSame(
            ['@id' => self::PAGE_URL . '#breadcrumb'],
            $this->entity(self::WEBPAGE_ID)['breadcrumb']
        );
    }

    public function testRawBreadcrumbIsLinkedFromWebPage(): void
    {
        SchemaRegistry::addEntity(self::PAGE_URL . '#breadcrumb', [
            '@type' => 'BreadcrumbList',
            '@id' => self::PAGE_URL . '#breadcrumb',
            'itemListElement' => [],
        ]);

        $this->assertSame(
            ['@id' => self::PAGE_URL . '#breadcrumb'],
            $this->entity(self::WEBPAGE_ID)['breadcrumb']
        );
    }

    private function entity(string $id): array
    {
        foreach (SchemaRegistry::getGraph() as $entity) {
            if (($entity['@id'] ?? null) === $id) {
                return $entity;
            }
        }

        $this->fail('Entity not found: ' . $id);
    }
}
