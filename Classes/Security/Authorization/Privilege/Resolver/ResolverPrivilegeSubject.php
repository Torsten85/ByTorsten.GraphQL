<?php
namespace ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver;

use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;

/**
 * A resolver privilege subject
 */
class ResolverPrivilegeSubject implements PrivilegeSubjectInterface
{
    /**
     * @var ResolverInfo
     */
    protected $resolveInfo;

    /**
     * @param ResolverInfo $resolveInfo
     */
    public function __construct(ResolverInfo $resolveInfo)
    {
        $this->resolveInfo = $resolveInfo;
    }

    /**
     * @return ResolverInfo
     */
    public function getResolveInfo(): ResolverInfo
    {
        return $this->resolveInfo;
    }
}
