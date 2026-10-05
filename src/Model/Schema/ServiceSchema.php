<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;

class ServiceSchema extends Schema
{
    public static function create(string $url, string $name, ?string $description = null): static
    {
        $pageURL = self::pageURL($url);
        $url = rtrim($url, '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'Service',
            '@id' => $url . '#service',
            'url' => $pageURL,
            'name' => $name,
            'isPartOf' => [
                '@id' => $url . '#webpage',
            ],
            'provider' => [
                '@id' => $baseURL . '#organisation',
            ],
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setAreaServed(string|array|null $area): static
    {
        if ($area) {
            $this->data['areaServed'] = $area;
        }

        return $this;
    }

    public function setProvider(string $id): static
    {
        $this->data['provider'] = [
            '@id' => $id,
        ];

        return $this;
    }

    public function setOffer(
        float|int|string $price,
        string $currency,
        ?string $url = null
    ): static {
        $offer = [
            '@type' => 'Offer',
            'priceCurrency' => $currency,
            'price' => $price,
        ];

        if ($url) {
            $offer['url'] = $url;
        } elseif (!empty($this->data['url'])) {
            $offer['url'] = $this->data['url'];
        }

        $this->data['offers'] = $offer;

        return $this;
    }
}
