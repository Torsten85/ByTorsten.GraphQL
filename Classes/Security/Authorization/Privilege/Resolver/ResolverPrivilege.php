<?php
namespace ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver;

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Context;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

class ResolverPrivilege extends AbstractPrivilege implements ResolverPrivilegeInterface
{

    /**
     * Returns TRUE, if this privilege covers the given subject
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException if the given $subject is not supported by the privilege
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if (!($subject instanceof ResolverPrivilegeSubject)) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s", but we got a subject of type: "%s".', ResolverPrivilege::class, ResolverPrivilegeSubject::class, get_class($subject)), 1416241149);
        }

        $resolveInfo = $subject->getResolveInfo();
        $eelContext = new Context(new ResolverPrivilegeContext($resolveInfo));

        $eelCompilingEvaluator = new CompilingEvaluator();
        return $eelCompilingEvaluator->evaluate($this->getParsedMatcher(), $eelContext);
    }
}