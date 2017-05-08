<?php
namespace ByTorsten\GraphQL\Routing;

use Neos\Flow\Annotations as Flow;
use ByTorsten\GraphQL\Service\EndpointService;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;

class EndpointRoutePartHandler extends DynamicRoutePart
{

    /**
     * @Flow\InjectConfiguration("endpoints")
     * @var array
     */
    protected $endpoints;

    /**
     * @param string $requestPath
     * @return bool
     */
    protected function matchValue($requestPath)
    {
        return $this->resolveValue($requestPath);
    }

    /**
     * @param string $endpoint
     * @return bool
     */
    protected function resolveValue($endpoint)
    {
        if (isset($this->endpoints[$endpoint])) {
            $this->value = strtolower($endpoint);
            return true;
        }

        return false;
    }
}