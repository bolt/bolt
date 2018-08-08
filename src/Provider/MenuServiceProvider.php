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
                $accessor = 'storage';
                if ($app['config']->get('general/compatibility/setcontent_legacy') === false) {
                    $accessor = 'query';
                }

                return new MenuBuilder($app, $accessor);
            }
        );

        /**
         * @internal backwards compatibility not guaranteed on this provider presently
         */
        $app['menu.admin_builder'] = function ($app) {
            $baseUrl = '';
            if (($request = $app['request_stack']->getCurrentRequest()) !== null) {
                $baseUrl = $request->getBasePath();
            }
            $baseUrl .= '/' . trim($app['controller.backend.mount_prefix'], '/');

            $rootEntry = MenuEntry::createRoot($app['url_generator'], $baseUrl);

            $builder = new Builder\AdminMenu();
            $builder->build($rootEntry);

            $contentTypes = Bag::fromRecursive($app['config']->get('contenttypes'));
            $builder = new Builder\AdminContent($contentTypes);
            $builder->build($rootEntry);

            return $rootEntry;
        };

        /**
         * @internal backwards compatibility not guaranteed on this provider presently
         */
        $app['menu.admin'] = $app->share(
            function ($app) {
                $token = $app['session']->get('authentication');
                if (!$token instanceof Token) {
                    return MenuEntry::create('root');
                }
                $user = $token->getUser();

                /** @var Stopwatch $watch */
                $watch = $app['stopwatch'];
                $rootEntry = $app['menu.admin_builder'];
                $contentTypes = Bag::from($app['config']->get('contenttypes'));

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
