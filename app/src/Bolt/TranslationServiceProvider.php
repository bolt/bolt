<?php

namespace Bolt;

use Bolt;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Loader as TranslationLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{

    public function register(\Silex\Application $app)
    {
        return null;
    }


    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registers
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(\Silex\Application $app)
    {
        if (isset($app['translator'])) {
            $loaders = array(
                'csv' => new TranslationLoader\CsvFileLoader(),
                'ini' => new TranslationLoader\IniFileLoader(),
                'mo'  => new TranslationLoader\MoFileLoader(),
                'php' => new TranslationLoader\PhpFileLoader(),
                'xlf' => new TranslationLoader\XliffFileLoader(),
                'yml' => new TranslationLoader\YamlFileLoader(),
            );
            $registeredLoaders = array();
            // Directory to look for translation file(s)
            $translationDir = dirname(dirname(__DIR__)) .
                '/resources/translations/' . $app['locale'];

            if (is_dir($translationDir)) {
                $iterator = new \DirectoryIterator($translationDir);
                /**
                 * @var \SplFileInfo $fileInfo
                 */
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile()) {
                        $extension = $fileInfo->getExtension();
                        if (array_key_exists($extension, $loaders)) {
                            if (!array_key_exists($extension, $registeredLoaders)) {
                                // TranslationFileLoader not yet registered
                                $app['translator']->addLoader($extension, $loaders[$extension]);
                            }
                            // There's a file, there's a loader, let's try
                            $fnameParts = explode(".", $fileInfo->getFilename());
                            $domain = $fnameParts[0];
                            $app['translator']->addResource($extension, $fileInfo->getRealPath(), $app['locale'], $domain);
                        }
                    }
                }
            }
        }
    }
}