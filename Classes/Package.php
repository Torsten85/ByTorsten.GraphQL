<?php
namespace ByTorsten\GraphQL;


use ByTorsten\GraphQL\Core\Cache\SchemaCache;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Command\CacheCommandController;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Package\PackageManagerInterface;
use ByTorsten\GraphQL\Core\Cache\FileMonitorListener;

class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $context = $bootstrap->getContext();
        if (!$context->isProduction()) {
            $dispatcher->connect(Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'neos.flow:systemfilemonitor') {
                    $fusionFileMonitor = FileMonitor::createFileMonitorAtBoot('GraphQL_Files', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(PackageManagerInterface::class);
                    foreach ($packageManager->getActivePackages() as $packageKey => $package) {
                        if ($packageManager->isPackageFrozen($packageKey)) {
                            continue;
                        }

                        $graphQlPaths = array(
                            $package->getResourcesPath() . 'Private/GraphQL'
                        );

                        foreach ($graphQlPaths as $graphQlPath) {
                            if (is_dir($graphQlPath)) {
                                $fusionFileMonitor->monitorDirectory($graphQlPath);
                            }
                        }
                    }

                    $fusionFileMonitor->detectChanges();
                    $fusionFileMonitor->shutdownObject();
                }

                if ($step->getIdentifier() === 'neos.flow:cachemanagement') {
                    $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
                    $packageManager = $bootstrap->getEarlyInstance(PackageManagerInterface::class);
                    $listener = new FileMonitorListener($cacheManager, $packageManager);
                    $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', $listener, 'flushSchemaCacheOnFileChanges');
                }
            });
        }

        $dispatcher->connect(CacheCommandController::class, 'warmupCaches', SchemaCache::class, 'warmup');
    }
}
