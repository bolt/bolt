<?php

namespace Bolt\Provider;

use Bolt\AccessControl\Token\Token;
use Bolt\Collection\Bag;
use Bolt\Menu\Builder;
use Bolt\Menu\MenuBuilder;
use Bolt\Menu\MenuEntry;
use Bolt\Menu\Resolver;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Stopwatch\Stopwatch;

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
                $token = $app['session']->get('authentication');
                if (!$token instanceof Token) {
                    return $rootEntry;
                }
                $user = $token->getUser();

                /** @var Stopwatch $watch */
                $watch = $app['stopwatch'];

                // ~ 1 ms
                $watch->start('menu.build.admin');
                $builder = new Builder\AdminMenu();
                $builder->build($rootEntry);
                $watch->stop('menu.build.admin');

                // ~ 2 ms
                $watch->start('menu.build.admin_content');
                $contentTypes = Bag::fromRecursive($app['config']->get('contenttypes'));
                $builder = new Builder\AdminContent($contentTypes);
                $builder->build($rootEntry);
                $watch->stop('menu.build.admin_content');

                // ~ 100 ms
                $watch->start('menu.resolve.recent');
                $resolver = new Resolver\RecentlyEdited($app['storage'], $app['markdown']);
                $resolver->resolve($rootEntry, $contentTypes);
                $watch->stop('menu.resolve.recent');

                // ~ 20 ms
                $watch->start('menu.resolve.access');
                $resolver = new Resolver\Access($app['permissions']);
                $resolver->resolve($rootEntry, $user);
                $watch->stop('menu.resolve.access');

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
