<?php
namespace Bolt\Provider;

use Bolt\Asset;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;

/**
 * HTML asset service providers.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 */
class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['asset.packages'] = $app->share(
            function ($app) {
                $defaultPackage = new Package($app['asset.version_strategy']('view'));
                $packages = new Packages($defaultPackage);

                $packages->addPackage('bolt', $app['asset.package_factory']('view'));
                $packages->addPackage('extensions', new PathPackage('', $app['asset.version_strategy']('web'), $app['asset.context']));
                $packages->addPackage('files', $app['asset.package_factory']('files'));
                $packages->addPackage('theme', $app['asset.package_factory']('theme'));

                return $packages;
            }
        );

        $app['asset.package_factory'] = $app->protect(
            function ($name) use ($app) {
                return new PathPackage(
                    $app['resources']->getUrl($name),
                    $app['asset.version_strategy']($name),
                    $app['asset.context']
                );
            }
        );

        $app['asset.version_strategy'] = $app->protect(
            function ($name) use ($app) {
                return new Asset\BoltVersionStrategy($app['filesystem']->getFilesystem($name), $app['asset.salt']);
            }
        );

        $app['asset.context'] = $app->share(
            function () use ($app) {
                return new RequestStackContext($app['request_stack']);
            }
        );

        $app['asset.salt.factory'] = function () use ($app) {
            return $app['randomgenerator']->generateString(10);
        };

        $app['asset.salt'] = $app->share(
            function ($app) {
                $file = $app['filesystem']->getFile('cache://.assetsalt');

                try {
                    $salt = $file->read();
                } catch (FileNotFoundException $e) {
                    $salt = $app['asset.salt.factory'];
                    $file->put($salt);
                }

                return $salt;
            }
        );

        $app['asset.injector'] = $app->share(
            function () {
                $snippets = new Asset\Injector();

                return $snippets;
            }
        );

        $app['asset.queue.file'] = $app->share(
            function ($app) {
                $queue = new Asset\File\Queue(
                    $app['asset.injector'],
                    $app['asset.packages']
                );

                return $queue;
            }
        );

        $app['asset.queue.snippet'] = $app->share(
            function ($app) {
                $queue = new Asset\Snippet\Queue(
                    $app['asset.injector'],
                    $app['cache'],
                    $app['config'],
                    $app['resources']
                );

                return $queue;
            }
        );

        $app['asset.queue.widget'] = $app->share(
            function ($app) {
                $queue = new Asset\Widget\Queue(
                    $app['asset.injector'],
                    $app['cache'],
                    $app['render']
                );

                return $queue;
            }
        );

        $app['asset.queues'] = $app->share(
            function ($app) {
                return [
                    $app['asset.queue.file'],
                    $app['asset.queue.snippet'],
                    $app['asset.queue.widget'],
                ];
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
