<?php
namespace ByTorsten\GraphQL\Resolver;

use GraphQL\Schema;

interface ContextInterface
{
    /**
     * @param Schema $schema
     * @param string $query
     * @param array $variables
     * @param string $operationName
     * @return mixed
     */
    function getContext(Schema $schema, string $query, array $variables, $operationName);
}