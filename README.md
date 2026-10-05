# Silverstripe Schema Manager

Structured-data management for Silverstripe CMS 6.

Schema Manager combines CMS-managed site information with automatic page schema and a request-scoped registry. Schema.org entities are emitted as a single linked JSON-LD `@graph` in the page `<head>`.

## Requirements

- PHP 8.3+
- Silverstripe CMS 6

## Installation

```bash
composer require dorsetdigital/silverstripe-schema-manager
vendor/bin/sake dev/build flush=1
```

## What is automatic?

By default Schema Manager adds site, page and breadcrumb schema to normal `ContentController` page requests:

```text
Organization
    ↑ publisher
WebSite
    ↑ isPartOf
WebPage

BreadcrumbList
```

Stable entity IDs are based on the canonical site/page URLs:

```text
https://example.com/#organisation
https://example.com/#website
https://example.com/about/#webpage
https://example.com/about/#breadcrumb
```

The JSON-LD is inserted automatically into the `<head>` of the HTML page response once it has been rendered, so schema registered from templates is included. No template change is required. Other HTML rendered during the request, such as emails, is left untouched.

## Automatic WebPage schema

Every normal `SiteTree` page receives a `WebPage` entity containing its URL, title, description where available, created/modified dates and a reference to the site's `WebSite` entity. No page extension is required.

## Configuration

Each automatic layer can be disabled independently:

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_organisation_schema: true
  automatic_website_schema: true
  automatic_webpage_schema: true
  automatic_breadcrumb_schema: true
```

## Supported schema types

Detailed examples and configuration are kept in separate documentation:

- [Site schema](docs/site-schema.md) — `Organization` and `WebSite`
- [Breadcrumbs](docs/breadcrumbs.md) — automatic and manual `BreadcrumbList`
- [FAQPage](docs/faq.md) — questions and accepted answers
- [ImageGallery](docs/image-gallery.md) — image collections and `ImageObject` metadata
- [Product](docs/product.md) — products, offers, stock and brands
- [Service](docs/service.md) — services, providers and pricing
- [JobPosting](docs/job-posting.md) — vacancies, locations and salaries
- [BlogPosting](docs/blog-posting.md) — optional Silverstripe Blog integration

Page-level typed builders link to their `WebPage` with `isPartOf` by default. When an entity is the primary subject of a dedicated page, `setMainEntityOfPage($pageURL)` promotes that relationship explicitly.

All typed builders inherit `Schema::update()`, allowing projects to add less common Schema.org properties without the module needing to model every possible field.

## Adding schema manually

Raw entities can still be registered directly:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

SchemaRegistry::addEntity(
    'https://example.com/#service',
    [
        '@type' => 'Service',
        '@id' => 'https://example.com/#service',
        'name' => 'Example service',
    ]
);
```

If an entity is registered again using the same `@id`, its data is recursively merged with the existing entity.

Typed schema objects can also be registered:

```php
SchemaRegistry::add($schema);
```

where `$schema` extends `DorsetDigital\SchemaManager\Model\Schema\Schema`.

## Extension point

The registry API can be called directly from application code; an extension hook is not required. For reusable integrations that need to contribute schema automatically to arbitrary pages, Schema Manager also calls this Silverstripe extension hook before page entities are registered:

```php
updateSchemaManagerEntities(array &$entities)
```

Extensions can therefore append, remove or modify typed schema objects without replacing the registry or controller integration. This is primarily an integration mechanism rather than a requirement for ordinary project code.

For example:

```php
use SilverStripe\Core\Extension;

class MyPageSchemaExtension extends Extension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        // Add another Schema object, or update an existing one.
    }
}
```

The same hook is called on `SiteConfig` for site-wide entities.

The bundled Blog integration uses this mechanism, so it also provides a reference implementation for future module integrations.

## Registry API

### `SchemaRegistry::add(Schema $schema)`

Registers a typed schema object. It can be called directly from controllers or other application code. The object is read when the graph is rendered, so changes made to it after registration are included. Relationships are also resolved at that point, so schemas registered independently still participate in automatic `WebPage.mainEntity` and breadcrumb relationship resolution.

### `SchemaRegistry::addEntity(string $id, array $data)`

Registers a raw Schema.org entity. Existing data under the same ID is recursively merged, including entities previously registered as typed schemas with `add()`. This allows project code to augment a typed entity without replacing it.

### `SchemaRegistry::addFAQ(string $question, string $answer, ?string $pageURL = null)`

Deprecated compatibility helper for adding FAQ questions. New code should use `FAQPageSchema` and `SchemaRegistry::add()`.

### `SchemaRegistry::addBreadCrumbs(SiteTree $page, int $maxDepth = 20, bool|string $stopAtPageType = false, bool $showHidden = false)`

Adds a `BreadcrumbList` using Silverstripe's standard breadcrumb hierarchy. The method remains available when automatic breadcrumb schema is disabled.

### `SchemaRegistry::getGraph(): array`

Returns all registered entities.

### `SchemaRegistry::getSchema(): array`

Returns the complete structure containing `@context` and `@graph`.

### `SchemaRegistry::getJSON(): string`

Returns JSON suitable for an `application/ld+json` script element.

### `SchemaRegistry::flush(): void`

Clears the request registry.

## Documentation

The `docs/` directory contains the detailed guides for each supported schema type. The README is intentionally focused on installation, automatic behaviour, configuration and the common registry API.

## Project structure

```text
_config/
  schema-manager.yml
src/
  Control/
    Middleware/
      SchemaMiddleware.php
    SchemaRegistry.php
  Extension/
    BlogPostSchemaExtension.php
    SchemaControllerExtension.php
    SiteConfigSchemaExtension.php
  Model/
    Schema/
      BlogPostingSchema.php
      FAQPageSchema.php
      ImageGallerySchema.php
      JobPostingSchema.php
      OrganisationSchema.php
      ProductSchema.php
      Schema.php
      ServiceSchema.php
      WebPageSchema.php
      WebsiteSchema.php
  Service/
    SchemaManager.php
```

The module is deliberately split into small responsibilities:

- **schema classes** build individual Schema.org entities
- **the registry** collects, merges and renders the graph
- **Silverstripe extensions** decide which entities should be registered for a request

This keeps the public API small while leaving room for additional schema types and project-specific extensions.

## License

BSD 3-Clause License.
