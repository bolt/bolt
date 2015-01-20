<?php
namespace Bolt\Provider;

use Bolt\Nut;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Application as ConsoleApplication;

class NutServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['nut'] = $app->share(
            function($app) {
                $console = new ConsoleApplication();

                $console->setName('Bolt console tool - Nut');
                if ($app instanceof \Bolt\Application) {
                    $console->setVersion($app->getVersion());
                }

                $console->addCommands($app['nut.commands']);

                return $console;
            }
        );

        $app['nut.commands'] = $app->share(
            function($app) {
                return array(
                    new Nut\CronRunner($app),
                    new Nut\CacheClear($app),
                    new Nut\Info($app),
                    new Nut\LogTrim($app),
                    new Nut\LogClear($app),
                    new Nut\DatabaseCheck($app),
                    new Nut\DatabaseRepair($app),
                    new Nut\TestRunner($app),
                    new Nut\ConfigGet($app),
                    new Nut\ConfigSet($app),
                    new Nut\Extensions($app),
                    new Nut\ExtensionsEnable($app),
                    new Nut\ExtensionsDisable($app),
                    new Nut\UserAdd($app),
                    new Nut\UserRoleAdd($app),
                    new Nut\UserRoleRemove($app),
                );
            }
        );

        // Maintain backwards compatibility
        $app['console'] = $app->share(
            function($app) {
                return $app['nut'];
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
