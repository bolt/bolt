<?php
namespace Bolt\Provider;

use Bolt\Nut;
use Bolt\Nut\NutApplication;
use LogicException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Command\Command;

class NutServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['nut'] = $app->share(
            function ($app) {
                $console = new NutApplication();

                $console->setName('Bolt console tool - Nut');
                if (isset($app['bolt_long_version'])) {
                    $console->setVersion($app['bolt_long_version']);
                }

                $console->addCommands($app['nut.commands']);

                return $console;
            }
        );

        $app['nut.commands'] = $app->share(
            function ($app) {
                return array(
                    new Nut\CronRunner($app),
                    new Nut\CacheClear($app),
                    new Nut\Info($app),
                    new Nut\LogTrim($app),
                    new Nut\LogClear($app),
                    new Nut\DatabaseCheck($app),
                    new Nut\DatabaseExport($app),
                    new Nut\DatabaseImport($app),
                    new Nut\DatabasePrefill($app),
                    new Nut\DatabaseRepair($app),
                    new Nut\TestRunner($app),
                    new Nut\ConfigGet($app),
                    new Nut\ConfigSet($app),
                    new Nut\Extensions($app),
                    new Nut\ExtensionsAutoloader($app),
                    new Nut\ExtensionsEnable($app),
                    new Nut\ExtensionsDisable($app),
                    new Nut\UserAdd($app),
                    new Nut\UserRoleAdd($app),
                    new Nut\UserRoleRemove($app),
                );
            }
        );

        /**
         * This is a shortcut to add commands to nut lazily.
         *
         * Add a single command:
         *
         *     $app['nut.commands.add'](function ($app) {
         *         return new Command1($app);
         *     });
         *
         * Or add multiple commands:
         *
         *     $app['nut.commands.add'](function ($app) {
         *         return [
         *             new Command1($app),
         *             new Command2($app),
         *         ];
         *     });
         *
         * Commands can also be passed in directly. However,
         * this is NOT recommended because commands are created
         * even when they are not used, e.g. web requests.
         *
         *     $app['nut.commands.add'](new Command1($app));
         */
        $app['nut.commands.add'] = $app->protect(
            function ($commandsToAdd) use ($app) {
                $app['nut.commands'] = $app->share(
                    $app->extend(
                        'nut.commands',
                        function ($existingCommands, $app) use ($commandsToAdd) {
                            if (is_callable($commandsToAdd)) {
                                $commandsToAdd = $commandsToAdd($app);
                            }
                            $commandsToAdd = is_array($commandsToAdd) ? $commandsToAdd : array($commandsToAdd);
                            foreach ($commandsToAdd as $command) {
                                if (!$command instanceof Command) {
                                    throw new LogicException(
                                        'Nut commands must be instances of \Symfony\Component\Console\Command\Command'
                                    );
                                }
                                $existingCommands[] = $command;
                            }

                            return $existingCommands;
                        }
                    )
                );
            }
        );

        // Maintain backwards compatibility
        $app['console'] = $app->share(
            function ($app) {
                return $app['nut'];
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
