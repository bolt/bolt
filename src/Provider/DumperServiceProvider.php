<?php

namespace Bolt\Provider;

use Bolt\Debug\Caster;
use Bolt\Twig\ArrayAccessSecurityProxy;
use Pimple\Container;
use Silex\Application;
use Silex\Provider\VarDumperServiceProvider;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

/**
 * DI for Symfony's VarDumper.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumperServiceProvider extends VarDumperServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        parent::register($app);

        $app['dump'] = $app->protect(
            function ($var) use ($app) {
                if (!$app['debug']) {
                    return;
                }
                $app['var_dumper']->dump($app['var_dumper.cloner']->cloneVar($var));
            }
        );

        $app['var_dumper'] = function ($app) {
            return PHP_SAPI === 'cli' ? $app['var_dumper.cli_dumper'] : $app['var_dumper.html_dumper'];
        };

        $app['var_dumper.html_dumper'] = function () {
            return new HtmlDumper();
        };

        $app['var_dumper.cloner'] = $app->extend(
            'var_dumper.cloner',
            function (VarCloner $cloner) {
                $cloner->addCasters(Caster\FilesystemCasters::getCasters());

                ArrayAccessSecurityProxy::registerCaster($cloner);

                return $cloner;
            }
        );
    }

    public function boot(Application $app)
    {
        if (!$app['debug']) {
            return;
        }

        // This code is here to lazy load the dump stack. This default
        // configuration for CLI mode is overridden in HTTP mode on
        // 'kernel.request' event
        VarDumper::setHandler(function ($var) use ($app) {
            VarDumper::setHandler($handler = function ($var) use ($app) {
                $app['var_dumper']->dump($app['var_dumper.cloner']->cloneVar($var));
            });
            $handler($var);
        });
    }
}
