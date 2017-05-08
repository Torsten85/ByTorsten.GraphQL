<?php
namespace ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver;

class ResolverPrivilegeContext
{

    /**
     * @var ResolverInfo
     */
    protected $resolveInfo;

    /**
     * @var array
     */
    public $source;

    /**
     * @var array
     */
    public $context;

    /**
     * @param ResolverInfo $resolveInfo
     */
    public function __construct(ResolverInfo $resolveInfo)
    {
        $this->resolveInfo = $resolveInfo;
        $this->source = $resolveInfo->getSource();
        $this->context = $resolveInfo->getContext();
    }

    /**
     * @param string $typeName
     * @return bool
     */
    public function isOfType(string $typeName): bool
    {
        return $this->resolveInfo->getType() === $typeName;
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function isField(string $fieldName): bool
    {
        return $this->resolveInfo->getFieldname() === $fieldName;
    }

    /**
     * @param string $endpoint
     * @return bool
     */
    public function isEndpoint(string $endpoint): bool
    {
        return $this->resolveInfo->getEndpoint() === $endpoint;
    }

}