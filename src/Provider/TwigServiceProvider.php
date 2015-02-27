<?php
namespace Bolt\Provider;

use Bolt\TwigExtension;
use Silex\Application;

class TwigServiceProvider extends \Silex\Provider\TwigServiceProvider
{
    public function register(Application $app)
    {
        parent::register($app);

        // Add the Bolt Twig Extension.
        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) {
                    $twig->addExtension(new TwigExtension($app));

                    return $twig;
                }
            )
        );

        $app['twig.path'] = function (Application $app) {
            return $app['config']->getTwigPath();
        };

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
