<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;

class WebPageSchema extends Schema
{
    public static function fromPage(SiteTree $page): static
    {
        $pageURL = self::pageURL($page->AbsoluteLink());
        $url = rtrim($pageURL, '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $pageURL,
            'name' => $page->Title,
            'isPartOf' => [
                '@id' => $baseURL . '#website',
            ],
        ];

        if ($page->MetaDescription) {
            $data['description'] = $page->MetaDescription;
        }

        $datePublished = self::formatDateTime($page->Created);

        if ($datePublished) {
            $data['datePublished'] = $datePublished;
        }

        $dateModified = self::formatDateTime($page->LastEdited);

        if ($dateModified) {
            $data['dateModified'] = $dateModified;
        }

        return new static($data);
    }

    public function setBreadcrumb(?Schema $schema): static
    {
        if ($schema) {
            $this->data['breadcrumb'] = ['@id' => $schema->getID()];
        } else {
            unset($this->data['breadcrumb']);
        }

        return $this;
    }

    public function setMainEntity(?Schema $schema): static
    {
        if ($schema) {
            $this->data['mainEntity'] = ['@id' => $schema->getID()];
        } else {
            unset($this->data['mainEntity']);
        }

        return $this;
    }
}
