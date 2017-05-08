<?php
namespace ByTorsten\GraphQL\Service;

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
            $resolverName = str_replace('@resolver', $type->name, $this->resolverControllerNameBase);
            $resolverName = $this->objectManager->getCaseSensitiveObjectName($resolverName);

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

                if ($type instanceof ObjectType) {
                    foreach ($type->getFields() as $field) {
                        $methodName = lcfirst($field->name) . 'Resolver';
                        if (method_exists($resolverName, $methodName)) {
                            $field->resolveFn = function () use ($resolverName, $methodName) {
                                $class = $this->objectManager->get($resolverName);
                                return call_user_func_array([$class, $methodName], func_get_args());
                            };
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
}