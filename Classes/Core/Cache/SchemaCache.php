<?php
namespace ByTorsten\GraphQL\Core\Cache;

use Neos\Flow\Annotations as Flow;

use ByTorsten\GraphQL\Service\GraphQLFileService;
use ByTorsten\GraphQL\Service\SchemaMerger;
use GraphQL\Schema;
use Neos\Cache\Frontend\PhpFrontend;
use ByTorsten\GraphQL\Service\SchemaBuilder;

/*
 * @Flow\Scope("singleton")
 */
class SchemaCache
{
    /**
     * @var PhpFrontend
     * @Flow\Inject
     */
    protected $cache;

    /**
     * @var array
     * @Flow\InjectConfiguration("endpoints")
     */
    protected $endpoints;

    /**
     * @var SchemaBuilder
     * @Flow\Inject
     */
    protected $schemaBuilder;

    /**
     * @var GraphQLFileService
     * @Flow\Inject
     */
    protected $graphQLFileService;

    /**
     * @param string $packageKey
     * @param string $subpackageKey
     * @return string
     */
    public static function generateCacheIdentifier(string $packageKey, string $subpackageKey = ''): string
    {
        return strtolower(strtr($packageKey . ($subpackageKey ? '.' . $subpackageKey : ''), '.:', '_-'));
    }

    /**
     * @param string $packageKey
     * @param string $subpackageKey
     * @return Schema
     */
    public function getSchemaForPackageKey(string $packageKey, string $subpackageKey = ''): Schema
    {
        $cacheIdentifier = static::generateCacheIdentifier($packageKey, $subpackageKey);
        $functionName = 'get_schema_' . $cacheIdentifier;

        if (function_exists($functionName)) {
            return $functionName();
        }
        if ($this->cache->has($cacheIdentifier)) {
            $this->cache->requireOnce($cacheIdentifier);

            return $functionName();
        } else {
            $types = $this->graphQLFileService->getContentForPackage($packageKey, $subpackageKey);
            $type = SchemaMerger::merge($types);
            $code = $this->schemaBuilder->buildSchemaCode($type, $functionName);
            $this->cache->set($cacheIdentifier, $code);
            return $this->schemaBuilder->getLastBuildSchema();
        }
    }

    /**
     * warms up every schema
     */
    public function warmup()
    {
        foreach($this->endpoints as $endpoint => $packageKey)
        {
            if (strpos($packageKey, '\\') !== false) {
                list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
            } else {
                $subpackageKey = null;
            }

            $this->getSchemaForPackageKey($packageKey, $subpackageKey ?? '');
        }
    }
}