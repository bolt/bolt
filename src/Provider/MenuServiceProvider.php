<?php

namespace Bolt\Provider;

use Bolt\Menu\MenuBuilder;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;
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
                $rootEntry = new MenuEntry('root', $app['config']->get('general/branding/path'));
                $extendEntry = (new MenuEntry('extend', 'extend'))
                    ->setLabel(Trans::__('Extensions'))
                    ->setIcon('fa:cubes')
                    ->setPermission('extensions')
                ;
                $rootEntry->addChild($extendEntry);

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
