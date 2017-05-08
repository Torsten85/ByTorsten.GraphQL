<?php
namespace ByTorsten\GraphQL\Service;

use Neos\Flow\Annotations as Flow;

use ByTorsten\GraphQL\Exception as GraphQLException;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Package\PackageManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class SchemaService  {

    /**
     * @Flow\InjectConfiguration("endpoints")
     * @var array
     */
    protected $endpoints;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $endpointConfiguartion = [];

    /**
     * @param string $endpoint
     * @return SchemaConfiguration
     * @throws GraphQLException
     */
    public function forEndpoint(string $endpoint): SchemaConfiguration
    {
        if (!isset($this->endpoints[$endpoint])) {
            throw new GraphQLException(sprintf('Unknown endpoint %s.', $endpoint));
        }
        if (!isset($this->endpointConfiguartion[$endpoint])) {
            $packageKey = $this->endpoints[$endpoint];
            if (strpos($packageKey, '\\') !== false) {
                list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
            } else {
                $subpackageKey = null;
            }

            $this->endpointConfiguartion[$endpoint] = new SchemaConfiguration($packageKey, $subpackageKey);
        }

        return $this->endpointConfiguartion[$endpoint];
    }
}