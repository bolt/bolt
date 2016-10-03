<?php

namespace Bolt\Provider;

use Bolt\Debug\Caster;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

/**
 * DI for Symfony's VarDumper.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumperServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['dump'] = $app->protect(
            function ($var) use ($app) {
                if (!$app['debug']) {
                    return;
                }
                $app['dumper']->dump($app['dumper.cloner']->cloneVar($var));
            }
        );

        VarDumper::setHandler(
            function ($var) use ($app) {
                /*
                 * Referencing $app['dump'] in anonymous function
                 * so the closure can be replaced in $app without
                 * breaking the reference here.
                 */
                return $app['dump']($var);
            }
        );

        $app['dumper'] = $app->share(
            function ($app) {
                return PHP_SAPI === 'cli' ? $app['dumper.cli'] : $app['dumper.html'];
            }
        );

        $app['dumper.cli'] = $app->share(
            function () {
                return new CliDumper();
            }
        );

        $app['dumper.html'] = $app->share(
            function () {
                return new HtmlDumper();
            }
        );

        $app['dumper.cloner'] = $app->share(
            function () {
                $cloner = new VarCloner();
                $cloner->addCasters(Caster\FilesystemCasters::getCasters());

                return $cloner;
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
