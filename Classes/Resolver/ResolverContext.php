<?php
namespace ByTorsten\GraphQL\Resolver;

use GraphQL\Schema;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;

class ResolverContext
{
    /**
     * @Flow\inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var string
     */
    protected $operationName;

    /**
     * @param Schema $schema
     * @param string $query
     * @param array $variables
     * @param string $operationName
     */
    public function __construct(Schema $schema, string $query, array $variables, $operationName = null)
    {
        $this->schema = $schema;
        $this->query = $query;
        $this->variables = $variables;
        $this->operationName = $operationName;
    }
}