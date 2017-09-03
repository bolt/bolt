<?php

namespace Bolt\Provider;

use Bolt\Common\Thrower;
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
                    'locale_fallbacks' => ['en_GB', 'en'],
                ]
            );
        }

        $previousLocale = $app->raw('locale');
        $app['locale'] = $app->share(function ($app) use ($previousLocale) {
            if (($locales = $app['config']->get('general/locale')) !== null) {
                $locales = (array) $locales;

                return reset($locales);
            }

            return $previousLocale;
        });

        $app['translator.caching'] = function ($app) {
            return (bool) $app['config']->get('general/caching/translations');
        };

        $app['translator.cache_dir'] = $app->share(function ($app) {
            if ($app['translator.caching'] === false) {
                return null;
            }

            return $app['path_resolver']->resolve('%cache%/trans');
        });

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

        // for javascript datetime calculations, timezone offset. e.g. "+02:00"
        $app['timezone_offset'] = date('P');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $locales = (array) $app['config']->get('general/locale');

        // Add fallback locales to list if they are not already there
        $locales = array_unique(array_merge($locales, $app['locale_fallbacks']));
        // Merge in generic versions of each locale
        $locales = $this->mergeGenericLocales($locales);
        // Merge in UTF-8 suffixes for each locale
        $locales = $this->mergeUtf8Locales($locales);
        // Set locales for native php...not sure why?
        setlocale(LC_ALL, $locales);

        $this->setDefaultTimezone($app);
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
                $app['path_resolver']->resolve('%site%/app/translation/'),
                $app['path_resolver']->resolve("%site%/app/translation/{$locale}"),
                $app['path_resolver']->resolve("%bolt%/app/resources/translations/{$locale}"),
                $app['path_resolver']->resolve("%root%/app/resources/translations/{$locale}"), // Will be done better in v3.4
            ]
        );

        $needsSecondPass = true;

        $resources = [];

        foreach ($transDirs as $transDir) {
            if (!is_dir($transDir) || !is_readable($transDir)) {
                continue;
            }
            $iterator = new \DirectoryIterator($transDir);
            /** @var \SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                $ext = pathinfo($fileInfo, PATHINFO_EXTENSION);
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

    protected function setDefaultTimezone(Application $app)
    {
        if (($timezone = $app['config']->get('general/timezone')) !== null) {
            date_default_timezone_set($timezone);

            return;
        }

        // PHP 7.0+ doesn't emit warning for no timezone set.
        if (PHP_MAJOR_VERSION > 5) {
            return;
        }

        // Run check to see if a default timezone has been set
        try {
            Thrower::call('date_default_timezone_get');
            $hasDefault = true;
        } catch (\ErrorException $e) {
            $hasDefault = false;
        }

        // If no default, set to UTC to prevent default not defined warnings
        if (!$hasDefault) {
            date_default_timezone_set('UTC');
        }
    }
}
