<?php
namespace ByTorsten\GraphQL\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class GraphQLFileService
{

    /**
     * @var PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @param string $packageKey
     * @param string $subpackageKey
     * @return array
     */
    public function getContentForPackage(string $packageKey, string $subpackageKey = ''): array
    {
        $package = $this->packageManager->getPackage($packageKey);
        $path = $package->getResourcesPath() . 'Private/GraphQL/' . $subpackageKey;
        $path = str_replace('\\\\', '\\', $path);

        $iterator = new \DirectoryIterator($path);
        $content = [];
        foreach($iterator as $file) {
            if ($file->isDot() || !$file->isFile()) {
                continue;
            }

            $content[] = file_get_contents($path . '/' . $file->getFilename());
        }

        return $content;
    }
}