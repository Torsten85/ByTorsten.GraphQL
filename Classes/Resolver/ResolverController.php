<?php
namespace ByTorsten\GraphQL\Resolver;

use ByTorsten\GraphQL\Exception;
use GraphQL\Type\Definition\ResolveInfo;

class ResolverController
{
    /**
     * @param $obj
     * @param $context
     * @param ResolveInfo $info
     * @return string
     * @throws Exception
     */
    public function resolveType($obj, $context, ResolveInfo $info): string
    {
        throw new Exception(sprintf('Schema cannot use Interface or Union types for execution. Please implement \'resolveType\' in %s', self::class ));
    }

    /**
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function serialize($value)
    {
        throw new Exception(sprintf('Cannot serialize value, please implmement %s::serialize', self::class));
    }

    /**
     * @param mixed $serializedValue
     * @return mixed
     * @throws Exception
     */
    public function parse($serializedValue)
    {
        throw new Exception(sprintf('Cannot parse value, please implmement %s::parse', self::class));
    }
}