<?php
namespace ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class ResolverInfo
{
    /**
     * @var mixed
     */
    protected $source;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var array
     */
    protected $path;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $fieldname;

    public function __construct($source, $arguments, $context, string $endpoint, ResolveInfo $resolveInfo)
    {
        $this->source = $source;
        $this->arguments = $arguments;
        $this->context = $context;
        $this->endpoint = $endpoint;
        $this->path = $resolveInfo->path;
        $this->type = $resolveInfo->parentType->name;
        $this->fieldname = $resolveInfo->fieldName;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFieldname(): string
    {
        return $this->fieldname;
    }
}