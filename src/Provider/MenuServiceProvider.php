<?php

namespace Bolt\Provider;

use Bolt\Menu\AdminMenuBuilder;
use Bolt\Menu\MenuBuilder;
use Bolt\Menu\MenuEntry;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class MenuServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['menu'] = 
            function ($app) {
                $builder = new MenuBuilder($app);

                return $builder;
            }
        ;

        /**
         * @internal Backwards compatibility not guaranteed on this provider presently.
         */
        $app['menu.admin'] = 
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
        ;
    }
}
