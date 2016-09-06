<?php
namespace Bolt\Provider;

use Bolt\Twig\DumpExtension;
use Bolt\Twig\FilesystemLoader;
use Bolt\Twig\Handler;
use Bolt\Twig\TwigExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Twig\Extension\AssetExtension;

class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['twig'])) {
            $app->register(new \Silex\Provider\TwigServiceProvider());
        }

        $app['twig.loader.bolt_filesystem'] = $app->share(
            function ($app) {
                $loader = new FilesystemLoader($app['filesystem']);

                $loader->addPath('theme://', 'theme');
                $loader->addPath('app://theme_defaults', 'theme');
                $loader->addPath('app://view/twig', 'bolt');

                /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
                $loader->addPath('theme://');
                $loader->addPath('app://theme_defaults');
                $loader->addPath('app://view/twig');

                return $loader;
            }
        );

        // Insert our filesystem loader before native one
        $app['twig.loader'] = $app->share(
            function ($app) {
                return new \Twig_Loader_Chain(
                    [
                        $app['twig.loader.array'],
                        $app['twig.loader.bolt_filesystem'],
                        $app['twig.loader.filesystem'],
                    ]
                );
            }
        );

        // Handlers
        $app['twig.handlers'] = $app->share(
            function (Application $app) {
                return new \Pimple(
                    [
                        // @codingStandardsIgnoreStart
                        'admin'  => $app->share(function () use ($app) { return new Handler\AdminHandler($app); }),
                        'array'  => $app->share(function () use ($app) { return new Handler\ArrayHandler($app); }),
                        'html'   => $app->share(function () use ($app) { return new Handler\HtmlHandler($app); }),
                        'image'  => $app->share(function () use ($app) { return new Handler\ImageHandler($app); }),
                        'record' => $app->share(function () use ($app) { return new Handler\RecordHandler($app); }),
                        'text'   => $app->share(function () use ($app) { return new Handler\TextHandler($app); }),
                        'user'   => $app->share(function () use ($app) { return new Handler\UserHandler($app); }),
                        'utils'  => $app->share(function () use ($app) { return new Handler\UtilsHandler($app); }),
                        'widget' => $app->share(function () use ($app) { return new Handler\WidgetHandler($app); }),
                        // @codingStandardsIgnoreEnd
                    ]
                );
            }
        );

        // Add the Bolt Twig Extension.
        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) {
                    $twig->addExtension(new TwigExtension($app, $app['twig.handlers'], false));
                    $twig->addExtension($app['twig.extension.asset']);

                    if (isset($app['dump'])) {
                        $twig->addExtension(new DumpExtension(
                            $app['dumper.cloner'],
                            $app['dumper.html'],
                            $app['users'],
                            $app['config']->get('general/debug_show_loggedoff', false)
                        ));
                    }

                    return $twig;
                }
            )
        );

        $app['twig.extension.asset'] = $app->share(
            function ($app) {
                return new AssetExtension($app['asset.packages']);
            }
        );

        $app['twig.loader.filesystem'] = $app->share(
            $app->extend(
                'twig.loader.filesystem',
                function ($filesystem, $app) {
                    $filesystem->addPath($app['resources']->getPath('app/view/twig'), 'bolt');

                    /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
                    $filesystem->addPath($app['resources']->getPath('app/view/twig'));

                    return $filesystem;
                }
            )
        );

        // Twig paths
        $app['twig.path'] = function () use ($app) {
            return $app['config']->getTwigPath();
        };

        // Twig options
        $app['twig.options'] = function () use ($app) {
            // Should we cache or not?
            if ($app['config']->get('general/caching/templates')) {
                $cache = $app['resources']->getPath('cache');
            } else {
                $cache = false;
            }

            return [
                'debug'            => true,
                'cache'            => $cache,
                'strict_variables' => $app['config']->get('general/strict_variables'),
                'autoescape'       => 'html',
            ];
        };

        $app['safe_twig.bolt_extension'] = function () use ($app) {
            return new TwigExtension($app, $app['twig.handlers'], true);
        };

        $app['safe_twig'] = $app->share(
            function ($app) {
                $loader = new \Twig_Loader_String();
                $twig = new \Twig_Environment($loader);
                $twig->addExtension($app['safe_twig.bolt_extension']);

                return $twig;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
