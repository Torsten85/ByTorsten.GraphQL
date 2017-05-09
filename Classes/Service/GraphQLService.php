<?php
namespace ByTorsten\GraphQL\Service;

use ByTorsten\GraphQL\Exception;
use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Type\Definition\ResolveInfo;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class GraphQLService
{
    /**
     *
     */
    public function initializeObject()
    {
        GraphQL::setDefaultFieldResolver(function () {
            return call_user_func_array([$this, 'defaultFieldResolver'], func_get_args());
        });
    }

    /**
     * @param $source
     * @param $args
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    protected function defaultFieldResolver($source, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $property = ObjectAccess::getProperty($source, $fieldName);
        return $property instanceof \Closure ? $property($source, $args, $context) : $property;
    }

    /**
     * @param Schema $schema
     * @param $requestString
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param mixed $variableValues
     * @param mixed $operationName
     * @return \GraphQL\Executor\ExecutionResult|\GraphQL\Executor\Promise\Promise
     */
    public function executeAndReturnResult(Schema $schema, $requestString, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null)
    {
        return GraphQL::executeAndReturnResult($schema, $requestString, $rootValue, $contextValue, $variableValues, $operationName);
    }
}