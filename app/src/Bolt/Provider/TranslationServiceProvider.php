<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Loader as TranslationLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
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
    public function boot(Application $app)
    {
        if (isset($app['translator'])) {
            $paths = $app['resources']->getPaths();

            $app['translator']->addLoader('yml', new TranslationLoader\YamlFileLoader());

            // Directory to look for translation file(s)
            $translationDir = $paths['apppath'] . '/resources/translations/' . $app['locale'];

            if (is_dir($translationDir)) {
                $iterator = new \DirectoryIterator($translationDir);
                /**
                 * @var \SplFileInfo $fileInfo
                 */
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && (getExtension($fileInfo->getFilename()) == "yml")) {
                        $fnameParts = explode(".", $fileInfo->getFilename());
                        $domain = $fnameParts[0];
                        $app['translator']->addResource('yml', $fileInfo->getRealPath(), $app['locale'], $domain);
                    }
                }
            }

            // Load fallback for infos domain
            $infosfilename = dirname(dirname(dirname(__DIR__))) . '/resources/translations/en/infos.en.yml';
            if (is_readable($infosfilename)) {
                $app['translator']->addResource('yml', $infosfilename, 'en', 'infos');
            }
        }
    }
}
