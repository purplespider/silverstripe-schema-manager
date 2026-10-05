<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;

class BlogPostingSchema extends Schema
{
    public static function fromBlogPost(DataObject $post): static
    {
        $pageURL = self::pageURL($post->AbsoluteLink());
        $url = rtrim($pageURL, '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'BlogPosting',
            '@id' => $url . '#blogposting',
            'url' => $pageURL,
            'headline' => $post->Title,
            'isPartOf' => [
                '@id' => $baseURL . '#website',
            ],
            'publisher' => [
                '@id' => $baseURL . '#organisation',
            ],
        ];

        if ($post->hasField('PublishDate') && $post->PublishDate) {
            $data['datePublished'] = $post->PublishDate;
        } elseif ($post->Created) {
            $data['datePublished'] = $post->Created;
        }

        if ($post->LastEdited) {
            $data['dateModified'] = $post->LastEdited;
        }

        if ($post->hasField('Summary') && $post->Summary) {
            $data['description'] = $post->Summary;
        } elseif ($post->hasField('MetaDescription') && $post->MetaDescription) {
            $data['description'] = $post->MetaDescription;
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
