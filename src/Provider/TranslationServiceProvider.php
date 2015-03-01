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
     *
     * @param \Silex\Application $app
     */
    public function boot(Application $app)
    {
        if (isset($app['translator'])) {
            $app['translator']->addLoader('yml', new TranslationLoader\YamlFileLoader());
            $app['translator']->addLoader('xlf', new TranslationLoader\XliffFileLoader());

            static::addResources($app, $app['locale']);

            // Load english fallbacks
            if ($app['locale'] != \Bolt\Application::DEFAULT_LOCALE) {
                static::addResources($app, \Bolt\Application::DEFAULT_LOCALE);
            }
        }
    }

    /**
     * Adds all resources that belong to a locale.
     *
     * @param Application $app
     * @param string      $locale
     */
    public static function addResources(Application $app, $locale)
    {
        // Directory to look for translation file(s)
        $transDir = $app['resources']->getPath('app/resources/translations/' . $locale);

        if (is_dir($transDir)) {
            $iterator = new \DirectoryIterator($transDir);
            /**
             * @var \SplFileInfo $fileInfo
             */
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }
                list($domain) = explode('.', $fileInfo->getFilename());
                $app['translator']->addResource($fileInfo->getExtension(), $fileInfo->getRealPath(), $locale, $domain);
            }
        } elseif (strlen($locale) == 5) {
            static::addResources($app, substr($locale, 0, 2));
        }
    }
}
