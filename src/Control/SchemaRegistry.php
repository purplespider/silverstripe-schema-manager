<?php

namespace DorsetDigital\SchemaManager\Control;

use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

class SchemaRegistry
{
    /**
     * Registrations per entity ID, in order: typed Schema objects and raw
     * arrays. Typed schemas are kept as objects and only read when the graph
     * is built, so changes made after registration are not lost.
     *
     * @var array<string, array<Schema|array>>
     */
    private static array $layers = [];

    public static function add(Schema $schema): void
    {
        $id = $schema->getID();

        if (!$id) {
            return;
        }

        self::$layers[$id][] = $schema;
    }

    public static function addEntity(string $id, array $data): void
    {
        self::$layers[$id][] = $data;
    }

    /**
     * @deprecated Use FAQPageSchema::create() and SchemaRegistry::add() instead.
     */
    public static function addFAQ(string $question, string $answer, ?string $pageURL = null): void
    {
        @trigger_error(
            __METHOD__ . '() is deprecated. Use FAQPageSchema::create() and SchemaRegistry::add() instead.',
            E_USER_DEPRECATED
        );

        if (!$pageURL) {
            $page = Director::get_current_page();

            if ($page && method_exists($page, 'AbsoluteLink')) {
                $pageURL = $page->AbsoluteLink();
            } else {
                $pageURL = Director::absoluteURL(Controller::curr()->getRequest()->getURL());
            }
        }

        $schema = FAQPageSchema::create($pageURL)->addQuestion($question, $answer);
        self::add($schema);
    }

    public static function addBreadCrumbs(
        SiteTree $page,
        int $maxDepth = 20,
        bool|string $stopAtPageType = false,
        bool $showHidden = false
    ): void {
        $items = $page->getBreadcrumbItems($maxDepth, $stopAtPageType, $showHidden);

        if (!$items || $items->count() < 2) {
            return;
        }

        $itemList = [];
        $position = 1;

        foreach ($items as $item) {
            if (!$item instanceof SiteTree) {
                continue;
            }

            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item->MenuTitle ?: $item->Title,
                'item' => $item->AbsoluteLink(),
            ];
        }

        if (count($itemList) < 2) {
            return;
        }

        $id = rtrim($page->AbsoluteLink(), '/') . '/#breadcrumb';

        self::addEntity($id, [
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => $itemList,
        ]);
    }

    /**
     * Builds the graph from the current state of every registration. Nothing
     * is cached or written back, so the graph can be built any number of
     * times and always reflects the latest data.
     */
    public static function getGraph(): array
    {
        $entities = [];
        $webPageIDs = [];

        // Later registrations under the same ID are merged over earlier ones
        foreach (self::$layers as $id => $layers) {
            $entities[$id] = array_replace_recursive(...array_map(
                fn (Schema|array $layer): array => $layer instanceof Schema ? $layer->toArray() : $layer,
                $layers
            ));

            foreach ($layers as $layer) {
                if ($layer instanceof WebPageSchema) {
                    $webPageIDs[] = $id;
                    break;
                }
            }
        }

        foreach ($webPageIDs as $webPageID) {
            self::resolveWebPageRelationships($webPageID, $entities);
        }

        return array_values($entities);
    }

    /**
     * Links a WebPage to its breadcrumb and main entity. When several
     * entities claim to be the main entity of the page, the last one
     * registered wins and the others become part of the page instead.
     */
    private static function resolveWebPageRelationships(string $webPageID, array &$entities): void
    {
        $pageURL = substr($webPageID, 0, -strlen('#webpage'));
        $breadcrumbID = $pageURL . '#breadcrumb';
        $mainEntityID = null;

        if (($entities[$breadcrumbID]['@type'] ?? null) === 'BreadcrumbList') {
            $entities[$webPageID]['breadcrumb'] = ['@id' => $breadcrumbID];
        }

        foreach ($entities as $id => $entity) {
            if ($id === $webPageID
                || ($entity['mainEntityOfPage']['@id'] ?? null) !== $webPageID
            ) {
                continue;
            }

            if ($mainEntityID !== null) {
                unset($entities[$mainEntityID]['mainEntityOfPage']);
                $entities[$mainEntityID]['isPartOf'] = ['@id' => $webPageID];
            }

            $mainEntityID = $id;
        }

        if ($mainEntityID !== null) {
            $entities[$webPageID]['mainEntity'] = ['@id' => $mainEntityID];
        }
    }

    public static function getSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => self::getGraph(),
        ];
    }

    public static function getJSON(): string
    {
        if (!self::$layers) {
            return '';
        }

        return (string) json_encode(
            self::getSchema(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    public static function flush(): void
    {
        self::$layers = [];
    }
}
