<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;

class JobPostingSchema extends Schema
{
    public static function create(string $url, string $title, ?string $description = null): static
    {
        $pageURL = self::pageURL($url);
        $url = rtrim($url, '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'JobPosting',
            '@id' => $url . '#jobposting',
            'url' => $pageURL,
            'title' => $title,
            'isPartOf' => [
                '@id' => $url . '#webpage',
            ],
            'hiringOrganization' => [
                '@id' => $baseURL . '#organisation',
            ],
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setHiringOrganization(string $id): static
    {
        $this->data['hiringOrganization'] = [
            '@id' => $id,
        ];

        return $this;
    }

    public function setDatePosted(?string $date): static
    {
        if ($date) {
            $this->data['datePosted'] = $date;
        }

        return $this;
    }

    public function setValidThrough(?string $date): static
    {
        if ($date) {
            $this->data['validThrough'] = $date;
        }

        return $this;
    }

    public function setEmploymentType(string|array|null $type): static
    {
        if ($type) {
            $this->data['employmentType'] = $type;
        }

        return $this;
    }

    public function setJobLocation(
        ?string $locality = null,
        ?string $region = null,
        ?string $country = null,
        ?string $streetAddress = null,
        ?string $postalCode = null
    ): static {
        $address = [
            '@type' => 'PostalAddress',
        ];

        if ($streetAddress) {
            $address['streetAddress'] = $streetAddress;
        }

        if ($locality) {
            $address['addressLocality'] = $locality;
        }

        if ($region) {
            $address['addressRegion'] = $region;
        }

        if ($postalCode) {
            $address['postalCode'] = $postalCode;
        }

        if ($country) {
            $address['addressCountry'] = $country;
        }

        if (count($address) > 1) {
            $this->data['jobLocation'] = [
                '@type' => 'Place',
                'address' => $address,
            ];
        }

        return $this;
    }

    public function setRemote(?string $country = null): static
    {
        $this->data['jobLocationType'] = 'TELECOMMUTE';

        if ($country) {
            $this->data['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name' => $country,
            ];
        }

        return $this;
    }

    public function setBaseSalary(
        float|int|string $value,
        string $currency,
        string $unit = 'YEAR'
    ): static {
        $this->data['baseSalary'] = [
            '@type' => 'MonetaryAmount',
            'currency' => $currency,
            'value' => [
                '@type' => 'QuantitativeValue',
                'value' => $value,
                'unitText' => $unit,
            ],
        ];

        $this->data['salaryCurrency'] = $currency;

        return $this;
    }

    public function setBaseSalaryRange(
        float|int|string $minValue,
        float|int|string $maxValue,
        string $currency,
        string $unit = 'YEAR'
    ): static {
        $this->data['baseSalary'] = [
            '@type' => 'MonetaryAmount',
            'currency' => $currency,
            'value' => [
                '@type' => 'QuantitativeValue',
                'minValue' => $minValue,
                'maxValue' => $maxValue,
                'unitText' => $unit,
            ],
        ];

        $this->data['salaryCurrency'] = $currency;

        return $this;
    }
}
