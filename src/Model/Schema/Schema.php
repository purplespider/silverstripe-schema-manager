<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Controller;

abstract class Schema
{
    public function __construct(protected array $data)
    {
    }

    public function getID(): string
    {
        return (string) ($this->data['@id'] ?? '');
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function setMainEntityOfPage(string $pageURL): static
    {
        $pageURL = rtrim($pageURL, '/') . '/';
        $webPageID = $pageURL . '#webpage';

        if (($this->data['isPartOf']['@id'] ?? null) === $webPageID) {
            unset($this->data['isPartOf']);
        }

        $this->data['mainEntityOfPage'] = ['@id' => $pageURL . '#webpage'];

        return $this;
    }

    public function isMainEntityOfPage(string $pageURL): bool
    {
        $pageURL = rtrim($pageURL, '/') . '/';

        return ($this->data['mainEntityOfPage']['@id'] ?? null) === $pageURL . '#webpage';
    }

    public function setIsPartOfPage(string $pageURL): static
    {
        $pageURL = rtrim($pageURL, '/') . '/';
        $webPageID = $pageURL . '#webpage';

        if (($this->data['mainEntityOfPage']['@id'] ?? null) === $webPageID) {
            unset($this->data['mainEntityOfPage']);
        }

        $this->data['isPartOf'] = ['@id' => $webPageID];

        return $this;
    }

    public function update(array $data): static
    {
        $this->data = array_replace_recursive($this->data, $data);
        return $this;
    }

    /**
     * Formats a page URL the way the site serves it, following
     * Controller.add_trailing_slash. Entity IDs keep their trailing slash
     * so they don't change with that setting.
     */
    public static function pageURL(string $url): string
    {
        return Controller::normaliseTrailingSlash($url);
    }
}
