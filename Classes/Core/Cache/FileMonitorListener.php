<?php
namespace ByTorsten\GraphQL\Core\Cache;


use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Package\PackageInterface;

/**
 * @Flow\Proxy(false)
 */
class FileMonitorListener
{
    /**
     * @var CacheManager
     */
    protected $flowCacheManager;

    /**
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @param CacheManager $flowCacheManager
     * @param PackageManagerInterface $packageManager
     */
    public function __construct(CacheManager $flowCacheManager, PackageManagerInterface $packageManager)
    {
        $this->flowCacheManager = $flowCacheManager;
        $this->packageManager = $packageManager;
    }

    /**
     * @param $fileMonitorIdentifier
     * @param array $changedFiles
     * @return void
     */
    public function flushSchemaCacheOnFileChanges($fileMonitorIdentifier, array $changedFiles)
    {
        $fileMonitorsThatTriggerSchemaCacheFlush = array(
            'GraphQL_Files'
        );

        if (in_array($fileMonitorIdentifier, $fileMonitorsThatTriggerSchemaCacheFlush)) {
            $cache = $this->flowCacheManager->getCache('ByTorsten_GraphQL_Schema');
            $files = array_keys($changedFiles);

            /** @var PackageInterface $package */
            foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
                if ($this->packageManager->isPackageFrozen($packageKey)) {
                    continue;
                }

                $graphQlBasePath = $package->getResourcesPath() . 'Private/GraphQL';

                foreach($files as $file) {
                    if (strpos($file, $graphQlBasePath) === 0) {
                        $relPath = substr($file, strlen($graphQlBasePath) + 1);
                        $subpackageKey = substr($relPath, 0, strpos($relPath, '/'));

                        $cacheIdentifier = SchemaCache::generateCacheIdentifier($packageKey, $subpackageKey);
                        $cache->remove($cacheIdentifier);
                    }
                }
            }
        }
    }
}
