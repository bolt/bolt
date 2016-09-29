<?php

namespace Bolt\Provider;

use Bolt\Menu\AdminMenuBuilder;
use Bolt\Menu\MenuBuilder;
use Bolt\Menu\MenuEntry;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MenuServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['menu'] = $app->share(
            function ($app) {
                $builder = new MenuBuilder($app);

                return $builder;
            }
        );

        /**
         * @internal Backwards compatibility not guaranteed on this provider presently.
         */
        $app['menu.admin'] = $app->share(
            function ($app) {
                // This service should not be invoked until request cycle since it depends
                // on url generation. Probably should be refactored somehow.
                $baseUrl = $app['url_generator']->generate('dashboard');
                $adminMenu = new AdminMenuBuilder(new MenuEntry('root', $baseUrl));
                $rootEntry = $adminMenu->build($app);

                return $rootEntry;
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
