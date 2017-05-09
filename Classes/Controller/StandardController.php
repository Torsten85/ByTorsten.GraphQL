<?php
namespace ByTorsten\GraphQL\Controller;

use Neos\Flow\Annotations as Flow;

use ByTorsten\GraphQL\Service\GraphQLService;
use ByTorsten\GraphQL\Service\SchemaService;
use Neos\Flow\Mvc\Controller\ActionController;
use ByTorsten\GraphQL\View\GraphQlView;

class StandardController extends ActionController
{

    /**
     * @var array
     */
    protected $supportedMediaTypes = ['application/json', 'text/html'];

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = ['json' => GraphQlView::class];

    /**
     * @Flow\Inject
     * @var SchemaService
     */
    protected $schemaService;

    /**
     * @Flow\Inject
     * @var GraphQLService
     */
    protected $graphQLService;

    /**
     * @param string $endpoint
     */
    public function indexAction($endpoint)
    {
        $this->view->assign('endpoint', $endpoint);
    }

    /**
     * @param string $endpoint The GraphQL endpoint, to allow for providing multiple APIs (this value is set from the routing usually)
     * @param string $query The GraphQL query string (see GraphQL::execute())
     * @param array $variables list of variables (if any, see GraphQL::execute()). Note: The variables can be JSON-serialized to a string (like GraphiQL does) or a "real" array
     * @param string $operationName The operation to execute (if multiple, see GraphQL::execute())
     * @return void
     * @Flow\SkipCsrfProtection
     */
    public function queryAction($endpoint, $query, $variables = null, $operationName = null)
    {
        if ($variables !== null && is_string($this->request->getArgument('variables'))) {
            $variables = json_decode($this->request->getArgument('variables'), true);
        }

        $schema = $this->schemaService->forEndpoint($endpoint)->getSchema();
        $result = $this->graphQLService->executeAndReturnResult($schema, $query, null, null, $variables, $operationName);
        $this->view->assign('result', $result);
    }
}