<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Loader as TranslationLoader;
use Bolt\Library as Lib;

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
            if ($app['locale'] != \Bolt\Application::DEFAULT_LOCALE) {
                $this->addResources($app, \Bolt\Application::DEFAULT_LOCALE);
            }
        }
    }

    /**
     * Adds all resources that belong to a locale
     *
     * @param Application $app
     * @param string $locale
     * @param string $territory
     */
    private function addResources(Application $app, $locale)
    {
        $paths = $app['resources']->getPaths();

        // Directory to look for translation file(s)
        $transDir = $paths['apppath'] . '/resources/translations/' . $locale;

        if (is_dir($transDir)) {
            $iterator = new \DirectoryIterator($transDir);
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
        } elseif (strlen($locale) == 5) {
            $this->addResources($app, substr($locale, 0, 2));
        }
    }
}
