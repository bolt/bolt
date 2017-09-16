<?php

namespace Bolt\Provider;

use Bolt;
use Bolt\Nut;
use Bolt\SimpleDeploy\Nut\SetupDeploy;
use LogicException;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Bridge;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;

class NutServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['nut'] = function ($app) {
            $console = new ConsoleApplication();

            $console->setName('Bolt console tool - Nut');
            $console->setVersion(Bolt\Version::VERSION);

            $console->getHelperSet()->set(new Nut\Helper\ContainerHelper($app));

            $console->addCommands($app['nut.commands']);

            $console->setDispatcher($app['dispatcher']);

            return $console;
        };

        $app['nut.command.twig_debug'] = function () { return new Bridge\Twig\Command\DebugCommand(); };
        $app['nut.command.twig_lint'] = function () { return new Bridge\Twig\Command\LintCommand(); };

        $app['nut.commands'] = function ($app) {
            $commands = [
                new Nut\CacheClear(),
                new Nut\ConfigGet(),
                new Nut\ConfigSet(),
                new Nut\Cron(),
                new Nut\DatabaseCheck(),
                new Nut\DatabaseExport(),
                new Nut\DatabaseImport(),
                new Nut\DatabaseOptimize(),
                new Nut\DatabasePrefill(),
                new Nut\DatabaseUpdate(),
                new Nut\Extensions(),
                new Nut\ExtensionsDumpAutoload(),
                new Nut\ExtensionsInstall(),
                new Nut\ExtensionsSetup(),
                new Nut\ExtensionsUninstall(),
                new Nut\ExtensionsUpdate(),
                new Nut\Info(),
                new Nut\Init(),
                new Nut\LogClear(),
                new Nut\LogTrim(),
                new Nut\PimpleDump(),
                new Nut\ServerRun(),
                new Nut\SetupRun(),
                new Nut\SetupSync(),
                new Nut\TestRunner(),
                new Nut\UserAdd(),
                new Nut\UserManage(),
                new Nut\UserResetPassword(),
                new Nut\UserRoleAdd(),
                new Nut\UserRoleRemove(),
                new Nut\DebugEvents(),
                new Nut\DebugServiceProviders(),
                new Nut\DebugRouter(),
                new Nut\RouterMatch(),
                new CompletionCommand(),
                $app['nut.command.twig_debug'],
                $app['nut.command.twig_lint'],
            ];
            if (class_exists(SetupDeploy::class)) {
                $commands[] = new SetupDeploy();
            }

            return $commands;
        };

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
                $app['nut.commands'] = $app->extend(
                    'nut.commands',
                    function ($existingCommands, $app) use ($commandsToAdd) {
                        if (is_callable($commandsToAdd)) {
                            $commandsToAdd = $commandsToAdd($app);
                        }
                        $commandsToAdd = is_array($commandsToAdd) ? $commandsToAdd : [$commandsToAdd];
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
                );
            }
        );
    }

    public function boot(Application $app)
    {
        if (PHP_SAPI === 'cli') {
            $app['nut.command.twig_debug']->setTwigEnvironment($app['twig']);
            $app['nut.command.twig_lint']->setTwigEnvironment($app['twig']);
        }
    }
}
