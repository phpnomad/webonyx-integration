<?php

namespace PHPNomad\GraphQL\Webonyx\Strategies;

use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use PHPNomad\Di\Interfaces\InstanceProvider;
use PHPNomad\GraphQL\Interfaces\GraphQLStrategy;
use PHPNomad\GraphQL\Interfaces\ResolverContext;
use PHPNomad\GraphQL\Interfaces\TypeDefinition;

class WebonyxGraphQLStrategy implements GraphQLStrategy
{
    /** @var callable[] */
    private array $typeDefinitionGetters = [];

    private ?Schema $schema = null;

    public function __construct(private readonly InstanceProvider $container)
    {
    }

    public function registerTypeDefinition(callable $definitionGetter): void
    {
        $this->typeDefinitionGetters[] = $definitionGetter;
        $this->schema = null;
    }

    public function execute(string $query, array $variables, ResolverContext $context): array
    {
        if ($this->schema === null) {
            $this->schema = $this->buildSchema();
        }

        return GraphQL::executeQuery($this->schema, $query, null, $context, $variables)->toArray();
    }

    private function buildSchema(): Schema
    {
        $sdl = "type Query { _placeholder: Boolean }\ntype Mutation { _placeholder: Boolean }\n";
        $resolverMap = [];

        foreach ($this->typeDefinitionGetters as $getter) {
            /** @var TypeDefinition $definition */
            $definition = ($getter)();
            $sdl .= $definition->getSdl() . "\n";

            foreach ($definition->getResolvers() as $typeName => $fields) {
                foreach ($fields as $fieldName => $resolverClass) {
                    $resolverMap[$typeName][$fieldName] = $resolverClass;
                }
            }
        }

        $container = $this->container;

        $typeConfigDecorator = function (array $typeConfig) use ($resolverMap, &$context, $container): array {
            $typeName = $typeConfig['name'];

            if (isset($resolverMap[$typeName])) {
                $typeResolvers = $resolverMap[$typeName];

                $typeConfig['resolveField'] = function (
                    mixed $rootValue,
                    array $args,
                    ResolverContext $ctx,
                    \GraphQL\Type\Definition\ResolveInfo $info
                ) use ($typeResolvers, $container) {
                    $fieldName = $info->fieldName;

                    if (isset($typeResolvers[$fieldName])) {
                        return $container->get($typeResolvers[$fieldName])->resolve($rootValue, $args, $ctx);
                    }

                    return Executor::defaultFieldResolver($rootValue, $args, $ctx, $info);
                };
            }

            return $typeConfig;
        };

        return BuildSchema::build($sdl, $typeConfigDecorator);
    }
}
