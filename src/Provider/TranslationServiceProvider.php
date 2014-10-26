<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Loader as TranslationLoader;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;

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
            $app['translator']->addLoader('yml', new TranslationLoader\YamlFileLoader());

            $this->addResources($app, $app['locale']);

            // Load english fallbacks
            if ($app['locale'] != 'en') {
                $this->addResources($app, 'en');
            }
        }
    }

    /**
     * Adds all resources that belong to a locale
     *
     * @param Application $app
     * @param string $locale
     */
    private function addResources(Application $app, $locale)
    {
        $paths = $app['resources']->getPaths();

        // Directory to look for translation file(s)
        $translationDir = $paths['apppath'] . '/resources/translations/' . $locale;

        if (is_dir($translationDir)) {
            $iterator = new \DirectoryIterator($translationDir);
            /**
             * @var \SplFileInfo $fileInfo
             */
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && (Lib::getExtension($fileInfo->getFilename()) == 'yml')) {
                    $fnameParts = explode('.', $fileInfo->getFilename());
                    $domain = $fnameParts[0];
                    $app['translator']->addResource('yml', $fileInfo->getRealPath(), $locale, $domain);
                }
            }
        }
    }
}
