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
                    'locale_fallbacks'     => ['en_GB', 'en'],
                ]
            );
        }

        $app['translator'] = $app->share(
            $app->extend(
                'translator',
                function ($translator, $app) {
                    foreach ($app['translator.loaders'] as $format => $loader) {
                        $translator->addLoader($format, $loader);
                    }

                    return $translator;
                }
            )
        );

        $app['translator.loaders'] = $app->share(
            function () {
                return [
                    'yml' => new TranslationLoader\YamlFileLoader(),
                    'xlf' => new TranslationLoader\XliffFileLoader(),
                ];
            }
        );

        $app['translator.resources'] = $app->extend(
            'translator.resources',
            function (array $resources, $app) {
                $locale = $app['locale'];

                $resources = array_merge($resources, static::addResources($app, $locale));

                foreach ($app['locale_fallbacks'] as $fallback) {
                    if ($locale !== $fallback) {
                        $resources = array_merge($resources, static::addResources($app, $fallback));
                    }
                }

                return $resources;
            }
        );

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
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * Adds all resources that belong to a locale.
     *
     * @param Application $app
     * @param string      $locale
     *
     * @return array
     */
    public static function addResources(Application $app, $locale)
    {
        // Directories to look for translation file(s)
        $transDirs = array_unique(
            [
                $app['resources']->getPath("app/resources/translations/{$locale}"),
                $app['resources']->getPath("root/app/resources/translations/{$locale}"),
            ]
        );

        $needsSecondPass = true;

        $resources = [];

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
                $resources[] = [$ext, $fileInfo->getRealPath(), $locale, $domain];
                $needsSecondPass = false;
            }
        }

        if ($needsSecondPass && strlen($locale) === 5) {
            $resources = array_merge($resources, static::addResources($app, substr($locale, 0, 2)));
        }

        return $resources;
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
