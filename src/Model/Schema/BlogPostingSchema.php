<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;

class BlogPostingSchema extends Schema
{
    public static function fromBlogPost(DataObject $post): static
    {
        $url = rtrim($post->AbsoluteLink(), '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'BlogPosting',
            '@id' => $url . '#blogposting',
            'url' => $url,
            'headline' => $post->Title,
            'isPartOf' => [
                '@id' => $baseURL . '#website',
            ],
            'publisher' => [
                '@id' => $baseURL . '#organisation',
            ],
        ];

        $datePublished = self::formatDateTime(
            ($post->hasField('PublishDate') ? $post->PublishDate : null) ?: $post->Created
        );

        if ($datePublished) {
            $data['datePublished'] = $datePublished;
        }

        $dateModified = self::formatDateTime($post->LastEdited);

        if ($dateModified) {
            $data['dateModified'] = $dateModified;
        }

        // Blog summaries are HTMLText, so strip the markup
        $description = ($post->hasField('Summary') ? self::plainText($post->Summary) : null)
            ?: ($post->hasField('MetaDescription') ? self::plainText($post->MetaDescription) : null);

        if ($description) {
            $data['description'] = $description;
        }

        if ($post->hasMethod('FeaturedImage')) {
            $image = $post->FeaturedImage();
            if ($image && $image->exists()) {
                $data['image'] = $image->getAbsoluteURL();
            }
        }

        return (new static($data))->setMainEntityOfPage($url);
    }
}
