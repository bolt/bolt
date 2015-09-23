<?php

namespace Bolt\Provider;

use Bolt\Library as Lib;
use Silex;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Loader as TranslationLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['translator'])) {
            $app->register(
                new Silex\Provider\TranslationServiceProvider(),
                [
                    'translator.cache_dir' => $app['resources']->getPath('cache/trans'),
                    'locale_fallbacks'     => ['en_GB', 'en']
                ]
            );
        }

        $locales = (array) $app['config']->get('general/locale');

        // Add fallback locales to list if they are not already there
        $locales = array_unique(array_merge($locales, $app['locale_fallbacks']));
        // Merge in generic versions of each locale
        $locales = $this->mergeGenericLocales($locales);
        // Merge in UTF-8 suffixes for each locale
        $locales = $this->mergeUtf8Locales($locales);
        // Set locales for native php...not sure why?
        setlocale(LC_ALL, $locales);

        // Set the default timezone if provided in the Config
        date_default_timezone_set($app['config']->get('general/timezone') ?: ini_get('date.timezone') ?: 'UTC');

        // for javascript datetime calculations, timezone offset. e.g. "+02:00"
        $app['timezone_offset'] = date('P');
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

            // Load fallbacks
            foreach ($app['locale_fallbacks'] as $fallback) {
                if ($app['locale'] !== $fallback) {
                    static::addResources($app, $fallback);
                }
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
        // Directories to look for translation file(s)
        $transDirs = array_unique([
            $app['resources']->getPath("app/resources/translations/{$locale}"),
            $app['resources']->getPath("root/app/resources/translations/{$locale}"),
        ]);

        $needsSecondPass = true;

        foreach ($transDirs as $transDir) {
            if (!is_dir($transDir) || !is_readable($transDir)) {
                continue;
            }
            $iterator = new \DirectoryIterator($transDir);
            /**
             * @var \SplFileInfo $fileInfo
             */
            foreach ($iterator as $fileInfo) {
                $ext = Lib::getExtension((string) $fileInfo);
                if (!$fileInfo->isFile() || !in_array($ext, ['yml', 'xlf'], true)) {
                    continue;
                }
                list($domain) = explode('.', $fileInfo->getFilename());
                $app['translator']->addResource($ext, $fileInfo->getRealPath(), $locale, $domain);
                $needsSecondPass = false;
            }
        }

        if ($needsSecondPass && strlen($locale) === 5) {
            static::addResources($app, substr($locale, 0, 2));
        }
    }

    /**
     * Adds generic locales into a given list.
     *
     * [fr_FR, es, en_GB, en_US] -> [fr_FR, fr, es, en_GB, en_US, en]
     *
     * @param string[] $inputLocales
     *
     * @return string[]
     */
    protected function mergeGenericLocales(array $inputLocales)
    {
        $locales = [];
        foreach ($inputLocales as $locale) {
            $locales[] = $locale;
            if (strlen($locale) === 5) {
                $locale = substr($locale, 0, 2);
                $locales[] = $locale;
            }
        }

        $locales = array_reverse(array_unique(array_reverse($locales)));

        return $locales;
    }

    /**
     * Adds UTF-8 suffixes for each locale in given list.
     *
     * @param string[] $inputLocales
     *
     * @return string[]
     */
    protected function mergeUtf8Locales(array $inputLocales)
    {
        $locales = [];
        foreach ($inputLocales as $locale) {
            $locales[] = $locale . '.UTF-8';
            $locales[] = $locale . '.utf8';
            $locales[] = $locale;
        }
        return $locales;
    }
}
