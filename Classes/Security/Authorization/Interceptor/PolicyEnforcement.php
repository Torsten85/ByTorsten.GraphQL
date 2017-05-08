<?php
namespace ByTorsten\GraphQL\Security\Authorization\Interceptor;

use ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver\ResolverInfo;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\EntityNotFoundException;
use ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver\ResolverPrivilegeInterface;
use ByTorsten\GraphQL\Security\Authorization\Privilege\Resolver\ResolverPrivilegeSubject;
use GraphQL\Type\Definition\ResolveInfo;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;

class PolicyEnforcement implements InterceptorInterface
{
    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @var ResolverInfo
     */
    protected $resolverInfo;

    /**
     * @param $source
     * @param $arguments
     * @param $context
     * @param string $endpoint
     * @param ResolveInfo $resolveInfo
     */
    public function setResolveInfo($source, $arguments, $context, string $endpoint, ResolveInfo $resolveInfo)
    {
        $this->resolverInfo = new ResolverInfo($source, $arguments, $context, $endpoint, $resolveInfo);
    }

    /**
     * @return bool
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function invoke()
    {
        $reason = '';
        $privilegeSubject = new ResolverPrivilegeSubject($this->resolverInfo);

        try {
            $this->authenticationManager->authenticate();
        } catch (EntityNotFoundException $exception) {
            throw new AuthenticationRequiredException('Could not authenticate. Looks like a broken session.', 1358971444, $exception);
        } catch (NoTokensAuthenticatedException $noTokensAuthenticatedException) {
            // We still need to check if the privilege is available to "Neos.Flow:Everybody".
            if ($this->privilegeManager->isGranted(ResolverPrivilegeInterface::class, $privilegeSubject, $reason) === false) {
                throw new NoTokensAuthenticatedException($noTokensAuthenticatedException->getMessage() . chr(10) . $reason, $noTokensAuthenticatedException->getCode());
            }
        }

        if ($this->privilegeManager->isGranted(ResolverPrivilegeInterface::class, $privilegeSubject, $reason) === false) {
            throw new AccessDeniedException($this->renderDecisionReasonMessage($reason), 1222268609);
        }

        return true;
    }

    /**
     * Returns a string message, giving insights what happened during privilege evaluation.
     *
     * @param string $privilegeReasonMessage
     * @return string
     */
    protected function renderDecisionReasonMessage($privilegeReasonMessage)
    {
        if (count($this->securityContext->getRoles()) === 0) {
            $rolesMessage = 'No authenticated roles';
        } else {
            $rolesMessage = 'Authenticated roles: ' . implode(', ', array_keys($this->securityContext->getRoles()));
        }

        return sprintf('Access denied for resolver' . chr(10) . chr(10) . '%s' . chr(10) . chr(10) . '%s', implode('.', $this->resolveInfo->path), $privilegeReasonMessage, $rolesMessage);
    }
}