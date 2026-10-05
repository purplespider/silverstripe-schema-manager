<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class ProductSchema extends Schema
{
    public static function create(string $url, string $name, ?string $description = null): static
    {
        $pageURL = self::pageURL($url);
        $url = rtrim($url, '/') . '/';

        $data = [
            '@type' => 'Product',
            '@id' => $url . '#product',
            'url' => $pageURL,
            'name' => $name,
            'isPartOf' => [
                '@id' => $url . '#webpage',
            ],
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setImage(?string $url): static
    {
        if ($url) {
            $this->data['image'] = $url;
        }

        return $this;
    }

    public function setOffer(
        float|int|string $price,
        string $currency,
        ?bool $inStock = null,
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

        if ($inStock !== null) {
            $offer['availability'] = $inStock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock';
        }

        $this->data['offers'] = $offer;

        return $this;
    }

    public function setSKU(?string $sku): static
    {
        if ($sku) {
            $this->data['sku'] = $sku;
        }

        return $this;
    }

    public function setBrand(?string $name): static
    {
        if ($name) {
            $this->data['brand'] = [
                '@type' => 'Brand',
                'name' => $name,
            ];
        }

        return $this;
    }
}
