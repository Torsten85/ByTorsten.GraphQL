<?php
namespace ByTorsten\GraphQL\Service;

use ByTorsten\GraphQL\Exception;
use ByTorsten\GraphQL\Resolver\ContextInterface;
use GraphQL\Type\Definition\CustomScalarType;
use Neos\Flow\Annotations as Flow;

use ByTorsten\GraphQL\Core\Cache\SchemaCache;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\UnionType;

use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class SchemaConfiguration {

    /**
     * @var string
     */
    protected $resolverControllerNamePattern = '@package\@subpackage\Resolver\@resolverResolverController';

    /**
     * @var string
     */
    protected $contextNamePattern = '@package\@subpackage\Resolver\Context';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var SchemaCache
     */
    protected $schemaCache;

    /**
     * @var string
     */
    protected $resolverControllerNameBase;

    /**
     * @var string
     */
    protected $scalarControllerNameBase;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var string
     */
    protected $packageKey;

    /**
     * @var string
     */
    protected $subpackageKey;

    /**
     * @param string $typeName
     * @return mixed
     */
    protected function getResolverClassNameFromType(string $typeName)
    {
        $resolverName = str_replace('@resolver', $typeName, $this->resolverControllerNameBase);
        return $this->objectManager->getCaseSensitiveObjectName($resolverName);
    }

    /**
     * @param string $packageKey
     * @param string $subpackageKey
     */
    public function __construct(string $packageKey, string $subpackageKey = null)
    {
        $this->packageKey = $packageKey;
        $this->subpackageKey = $subpackageKey ?? '';

        $this->resolverControllerNameBase = $this->resolverControllerNamePattern;
        $this->resolverControllerNameBase = str_replace('@package', str_replace('.', '\\', $packageKey), $this->resolverControllerNameBase);
        $this->resolverControllerNameBase = str_replace('@subpackage', $subpackageKey, $this->resolverControllerNameBase);
        $this->resolverControllerNameBase = str_replace('\\\\', '\\', $this->resolverControllerNameBase);
    }

    /**
     * @return Schema
     */
    protected function generateExecutableSchema(): Schema
    {
        $schema = $this->schemaCache->getSchemaForPackageKey($this->packageKey, $this->subpackageKey);

        $types = array_filter($schema->getTypeMap(), function ($type) {
            return substr($type->name, 0, 2) !== '__' && (
                $type instanceof ObjectType ||
                $type instanceof InterfaceType ||
                $type instanceof UnionType ||
                $type instanceof CustomScalarType
            );
        });

        /** @var Type $type */
        foreach ($types as $type) {
            $possibleTypeNames = [$type->name];

            if ($type instanceof ObjectType) {
                $possibleTypeNames = array_merge($possibleTypeNames, array_map(function (InterfaceType $interface) {
                    return $interface->name;
                }, $type->getInterfaces()));
            }

            $resolverNames = array_map(function ($possibleTypeName) {
                return $this->getResolverClassNameFromType($possibleTypeName);
            }, $possibleTypeNames);

            $resolverName = $resolverNames[0];

            if ($resolverName !== false) {

                if ($type instanceof InterfaceType || $type instanceof UnionType) {
                    $type->config['resolveType'] = function () use ($resolverName) {
                        $class = $this->objectManager->get($resolverName);
                        return call_user_func_array([$class, 'resolveType'], func_get_args());
                    };
                };

                if ($type instanceof CustomScalarType) {
                    $type->config['serialize'] = function () use ($resolverName) {
                        $class = $this->objectManager->get($resolverName);
                        return call_user_func_array([$class, 'serialize'], func_get_args());
                    };

                    $type->config['parseValue'] = function () use ($resolverName) {
                        $class = $this->objectManager->get($resolverName);
                        return call_user_func_array([$class, 'parse'], func_get_args());
                    };
                }
            }

            if ($type instanceof ObjectType || $type instanceof InterfaceType) {
                foreach ($type->getFields() as $field) {
                    foreach($resolverNames as $resolverName) {
                        $methodName = lcfirst($field->name) . 'Resolver';
                        if (method_exists($resolverName, $methodName)) {
                            $field->resolveFn = function () use ($resolverName, $methodName) {
                                $class = $this->objectManager->get($resolverName);
                                return call_user_func_array([$class, $methodName], func_get_args());
                            };
                            break;
                        }
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        if (!$this->schema) {
            $this->schema = $this->generateExecutableSchema();
        }

        return $this->schema;
    }

    /**
     * @param $schema
     * @param string $query
     * @param array $variables
     * @param string $operationName
     * @return mixed
     * @throws Exception
     */
    public function getContext($schema, string $query, $variables, $operationName)
    {
        $contextName = $this->contextNamePattern;
        $contextName = str_replace('@package', str_replace('.', '\\', $this->packageKey), $contextName);
        $contextName = str_replace('@subpackage', $this->subpackageKey, $contextName);
        $contextName = str_replace('\\\\', '\\', $contextName);
        $contextName = $this->objectManager->getCaseSensitiveObjectName($contextName);

        if ($contextName) {
            $context = $this->objectManager->get($contextName);

            if (!$context instanceof ContextInterface) {
                throw new Exception(sprintf('%s has to implement %s', $contextName, ContextInterface::class));
            }

            return $context->getContext($schema, $query, $variables ?? [], $operationName);
        }
    }
}