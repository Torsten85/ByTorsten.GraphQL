<?php
namespace ByTorsten\GraphQL\Http;

use Neos\Flow\Annotations as Flow;

use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;

class HttpOptionsComponent implements ComponentInterface
{
    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $endpoints;

    /**
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();
        if ($httpRequest->getMethod() !== 'OPTIONS') {
            return;
        }

        if (!isset($this->endpoints[$httpRequest->getRelativePath()])) {
            return;
        }

        $httpResponse = $componentContext->getHttpResponse();
        $httpResponse->setHeader('Allow', 'GET, POST');
        $componentContext->setParameter(ComponentChain::class, 'cancel', true);
    }
}
