# phpnomad/webonyx-integration

[![Latest Version](https://img.shields.io/packagist/v/phpnomad/webonyx-integration.svg)](https://packagist.org/packages/phpnomad/webonyx-integration)
[![Total Downloads](https://img.shields.io/packagist/dt/phpnomad/webonyx-integration.svg)](https://packagist.org/packages/phpnomad/webonyx-integration)
[![PHP Version](https://img.shields.io/packagist/php-v/phpnomad/webonyx-integration.svg)](https://packagist.org/packages/phpnomad/webonyx-integration)
[![License](https://img.shields.io/packagist/l/phpnomad/webonyx-integration.svg)](https://packagist.org/packages/phpnomad/webonyx-integration)

Integrates [`webonyx/graphql-php`](https://github.com/webonyx/graphql-php) with PHPNomad's GraphQL layer. Provides the concrete `GraphQLStrategy` that `phpnomad/graphql` declares as an abstraction, so applications can serve GraphQL queries through the webonyx engine without binding their resolvers to it.

## Installation

```bash
composer require phpnomad/webonyx-integration
```

## What This Provides

- `WebonyxGraphQLStrategy` implements `GraphQLStrategy` from `phpnomad/graphql`. It stitches together SDL fragments and resolver maps contributed by registered `TypeDefinition`s, builds one webonyx `Schema`, and executes queries through `GraphQL::executeQuery()`.
- Resolver classes are resolved out of a `phpnomad/di` `InstanceProvider`, so a `FieldResolver` can depend on datastores, services, or any other bound class through normal constructor injection. The schema is built lazily on the first `execute()` call and rebuilt when a new type definition is registered.

## Requirements

- PHP 8.2+
- `phpnomad/graphql`
- `phpnomad/di`
- `webonyx/graphql-php` ^15.0

## Usage

Bind `WebonyxGraphQLStrategy` to the `GraphQLStrategy` interface in your container, then register a `TypeDefinition` for each slice of your schema.

```php
<?php

use PHPNomad\GraphQL\Interfaces\FieldResolver;
use PHPNomad\GraphQL\Interfaces\GraphQLStrategy;
use PHPNomad\GraphQL\Interfaces\ResolverContext;
use PHPNomad\GraphQL\Interfaces\TypeDefinition;
use PHPNomad\GraphQL\Webonyx\Strategies\WebonyxGraphQLStrategy;

$container->bind(GraphQLStrategy::class, WebonyxGraphQLStrategy::class);

class PostSchema implements TypeDefinition
{
    public function getSdl(): string
    {
        return <<<SDL
            type Post { id: ID! title: String! }
            extend type Query { posts: [Post!]! }
        SDL;
    }

    public function getResolvers(): array
    {
        return [
            'Query' => ['posts' => PostsQueryResolver::class],
        ];
    }
}

class PostsQueryResolver implements FieldResolver
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    public function resolve(mixed $rootValue, array $args, ResolverContext $context): mixed
    {
        return $this->posts->all();
    }
}

$strategy = $container->get(GraphQLStrategy::class);
$strategy->registerTypeDefinition(fn() => new PostSchema());

/** @var ResolverContext $context */
$result = $strategy->execute('{ posts { id title } }', [], $context);
```

Because resolvers are referenced by class name and instantiated through the container, their constructor dependencies wire through the same DI bindings as the rest of the application.

## Documentation

Framework docs live at [phpnomad.com](https://phpnomad.com). For the underlying engine, see the [`webonyx/graphql-php` repository](https://github.com/webonyx/graphql-php) and its schema and resolver reference.

## License

MIT. See [LICENSE](LICENSE).
