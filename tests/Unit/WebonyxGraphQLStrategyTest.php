<?php

namespace PHPNomad\GraphQL\Webonyx\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use PHPNomad\Di\Interfaces\InstanceProvider;
use PHPNomad\GraphQL\Interfaces\FieldResolver;
use PHPNomad\GraphQL\Interfaces\ResolverContext;
use PHPNomad\GraphQL\Interfaces\TypeDefinition;
use PHPNomad\GraphQL\Webonyx\Strategies\WebonyxGraphQLStrategy;

class WebonyxGraphQLStrategyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteReturnsDataFromResolver(): void
    {
        $resolver = new class implements FieldResolver {
            public function resolve(mixed $rootValue, array $args, ResolverContext $context): mixed
            {
                return [
                    ['id' => '1', 'title' => 'The Pragmatic Programmer'],
                    ['id' => '2', 'title' => 'Clean Code'],
                ];
            }
        };

        $resolverClass = get_class($resolver);

        $typeDefinition = new class($resolverClass) implements TypeDefinition {
            public function __construct(private string $resolverClass)
            {
            }

            public function getSdl(): string
            {
                return "type Book { id: ID! title: String! }\nextend type Query { books: [Book!]! }";
            }

            public function getResolvers(): array
            {
                return [
                    'Query' => [
                        'books' => $this->resolverClass,
                    ],
                ];
            }
        };

        $container = Mockery::mock(InstanceProvider::class);
        $container->shouldReceive('get')->with($resolverClass)->andReturn($resolver);

        $context = Mockery::mock(ResolverContext::class);

        $strategy = new WebonyxGraphQLStrategy($container);
        $strategy->registerTypeDefinition(fn() => $typeDefinition);

        $result = $strategy->execute('{ books { id title } }', [], $context);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']['books']);
        $this->assertSame('1', $result['data']['books'][0]['id']);
        $this->assertSame('The Pragmatic Programmer', $result['data']['books'][0]['title']);
        $this->assertSame('2', $result['data']['books'][1]['id']);
        $this->assertSame('Clean Code', $result['data']['books'][1]['title']);
    }

    public function testSchemaIsRebuildAfterNewDefinitionRegistered(): void
    {
        $container = Mockery::mock(InstanceProvider::class);
        $context = Mockery::mock(ResolverContext::class);

        $resolver = new class implements FieldResolver {
            public function resolve(mixed $rootValue, array $args, ResolverContext $context): mixed
            {
                return 'hello';
            }
        };

        $resolverClass = get_class($resolver);
        $container->shouldReceive('get')->with($resolverClass)->andReturn($resolver);

        $typeDefinition = new class($resolverClass) implements TypeDefinition {
            public function __construct(private string $resolverClass)
            {
            }

            public function getSdl(): string
            {
                return "extend type Query { greeting: String }";
            }

            public function getResolvers(): array
            {
                return [
                    'Query' => ['greeting' => $this->resolverClass],
                ];
            }
        };

        $strategy = new WebonyxGraphQLStrategy($container);

        // First execute — no types registered yet, just Query/Mutation base
        $result = $strategy->execute('{ __typename }', [], $context);
        $this->assertArrayHasKey('data', $result);

        // Register definition — schema should be invalidated
        $strategy->registerTypeDefinition(fn() => $typeDefinition);

        $result = $strategy->execute('{ greeting }', [], $context);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('hello', $result['data']['greeting']);
    }

    public function testDefaultPropertyResolutionUsedWhenNoResolverRegistered(): void
    {
        $container = Mockery::mock(InstanceProvider::class);
        $context = Mockery::mock(ResolverContext::class);

        $typeDefinition = new class implements TypeDefinition {
            public function getSdl(): string
            {
                return "type Author { name: String }\nextend type Query { author: Author }";
            }

            public function getResolvers(): array
            {
                $authorResolver = new class implements FieldResolver {
                    public function resolve(mixed $rootValue, array $args, ResolverContext $context): mixed
                    {
                        return ['name' => 'Alex'];
                    }
                };

                return [
                    'Query' => ['author' => get_class($authorResolver)],
                ];
            }
        };

        $resolvers = $typeDefinition->getResolvers();
        $authorResolverClass = $resolvers['Query']['author'];
        $authorResolver = new $authorResolverClass();

        $container->shouldReceive('get')->with($authorResolverClass)->andReturn($authorResolver);

        $strategy = new WebonyxGraphQLStrategy($container);
        $strategy->registerTypeDefinition(fn() => $typeDefinition);

        $result = $strategy->execute('{ author { name } }', [], $context);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame('Alex', $result['data']['author']['name']);
    }
}
