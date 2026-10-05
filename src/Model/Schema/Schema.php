<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\ORM\FieldType\DBField;

abstract class Schema
{
    public function __construct(protected array $data)
    {
    }

    /**
     * Formats a stored Silverstripe datetime as ISO 8601 with a timezone
     * offset, e.g. 2025-05-02T08:40:51+01:00.
     */
    public static function formatDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return DBField::create_field('Datetime', $value)->Rfc3339();
    }

    /**
     * Converts HTML to a single line of plain text. Returns null when nothing
     * but markup or whitespace remains, e.g. "<p>&nbsp;</p>".
     */
    public static function plainText(?string $html): ?string
    {
        $text = DBField::create_field('HTMLText', (string) $html)->Plain();
        $text = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));

        return $text !== '' ? $text : null;
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
}
