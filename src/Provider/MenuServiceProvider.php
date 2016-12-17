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
                // on url generation and request base path. Probably should be refactored somehow.
                $baseUrl = '';
                if (($request = $app['request_stack']->getCurrentRequest()) !== null) {
                    $baseUrl = $request->getBasePath();
                }
                $baseUrl .= '/' . trim($app['controller.backend.mount_prefix'], '/');

                $rootEntry = MenuEntry::createRoot($app['url_generator'], $baseUrl);

                $builder = new AdminMenuBuilder();
                $builder->build($rootEntry);

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
