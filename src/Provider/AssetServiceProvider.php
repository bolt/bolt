<?php
namespace Bolt\Provider;

use Bolt\Asset;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Webmozart\PathUtil\Path;

/**
 * HTML asset service providers.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 */
class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['asset.packages'] = 
            function ($app) {
                $packages = new Packages();

                $bolt = $app['asset.package_factory']('bolt_assets');
                $packages->addPackage('bolt', $bolt);
                $packages->addPackage('bolt_assets', $bolt); // For FS plugin

                $packages->addPackage('extensions', new PathPackage('', $app['asset.version_strategy']('web'), $app['asset.context']));
                $packages->addPackage('files', $app['asset.package_factory']('files'));
                $packages->addPackage('theme', $app['asset.package_factory']('theme'));
                $packages->addPackage('themes', $app['asset.package_factory']('themes'));

                return $packages;
            }
        ;

        $app['asset.package_factory'] = $app->protect(
            function ($name) use ($app) {
                $path = $app['path_resolver']->resolve($name);
                $web = $app['path_resolver']->resolve('web');
                $basePath = Path::makeRelative($path, $web);

                return new PathPackage(
                    $basePath,
                    $app['asset.version_strategy']($name),
                    $app['asset.context']
                );
            }
        );

        $app['asset.version_strategy'] = $app->protect(
            function ($nameOrDir) use ($app) {
                $dir = $nameOrDir instanceof DirectoryInterface ? $nameOrDir :
                    $app['filesystem']->getFilesystem($nameOrDir)->getDir('');

                return new Asset\BoltVersionStrategy($dir, $app['asset.salt']);
            }
        );

        $app['asset.context'] = 
            function () use ($app) {
                return new RequestStackContext($app['request_stack']);
            }
        ;

        $app['asset.salt.factory'] = function () use ($app) {
            return $app['randomgenerator']->generateString(10);
        };

        $app['asset.salt'] = 
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
        ;

        $app['asset.injector'] = 
            function () {
                $snippets = new Asset\Injector();

                return $snippets;
            }
        ;

        $app['asset.queue.file'] = 
            function ($app) {
                $queue = new Asset\File\Queue(
                    $app['asset.injector'],
                    $app['asset.packages'],
                    $app['config']
                );

                return $queue;
            }
        ;

        $app['asset.queue.snippet'] = 
            function ($app) {
                $queue = new Asset\Snippet\Queue(
                    $app['asset.injector'],
                    $app['cache']
                );

                return $queue;
            }
        ;

        $app['asset.queue.widget'] = 
            function ($app) {
                $queue = new Asset\Widget\Queue(
                    $app['asset.injector'],
                    $app['cache'],
                    $app['twig']
                );

                return $queue;
            }
        ;

        $app['asset.queues'] = 
            function ($app) {
                return [
                    $app['asset.queue.file'],
                    $app['asset.queue.snippet'],
                    $app['asset.queue.widget'],
                ];
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
