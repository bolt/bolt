<?php
namespace Bolt\Provider;

use Bolt\Twig\Handler\AdminHandler;
use Bolt\Twig\Handler\ArrayHandler;
use Bolt\Twig\Handler\HtmlHandler;
use Bolt\Twig\Handler\ImageHandler;
use Bolt\Twig\Handler\RecordHandler;
use Bolt\Twig\Handler\TextHandler;
use Bolt\Twig\Handler\UserHandler;
use Bolt\Twig\Handler\UtilsHandler;
use Bolt\Twig\TwigExtension;
use Silex\Application;

class TwigServiceProvider extends \Silex\Provider\TwigServiceProvider
{
    public function register(Application $app)
    {
        parent::register($app);

        // Handlers
        $app['twig.handlers'] = $app->share(
            function ($app) {
                return array(
                    'admin'  => function (Application $app) { return new AdminHandler($app); },
                    'array'  => function (Application $app) { return new ArrayHandler($app); },
                    'html'   => function (Application $app) { return new HtmlHandler($app); },
                    'image'  => function (Application $app) { return new ImageHandler($app); },
                    'record' => function (Application $app) { return new RecordHandler($app); },
                    'text'   => function (Application $app) { return new TextHandler($app); },
                    'user'   => function (Application $app) { return new UserHandler($app); },
                    'utils'  => function (Application $app) { return new UtilsHandler($app); },
                );
        });

        // Add the Bolt Twig Extension.
        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) {
                    $twig->addExtension(new TwigExtension($app, $app['twig.handlers'], false));

                    return $twig;
                }
            )
        );

        // Twig paths
        $app['twig.path'] = function (Application $app) {
            return $app['config']->getTwigPath();
        };

        // Twig options
        $app['twig.options'] = function (Application $app) {
            // Should we cache or not?
            if ($app['config']->get('general/caching/templates')) {
                $cache = $app['resources']->getPath('cache');
            } else {
                $cache = false;
            }

            return array(
                'debug'            => true,
                'cache'            => $cache,
                'strict_variables' => $app['config']->get('general/strict_variables'),
                'autoescape'       => true,
            );
        };
    }
}
