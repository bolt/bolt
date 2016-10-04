<?php
namespace Bolt\Provider;

use Bolt\Asset;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
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
                $packages = new Packages();

                $packages->addPackage('bolt', $app['asset.package.bolt']);
                $packages->addPackage('extensions', new PathPackage('', $app['asset.version_strategy']('web'), $app['asset.context']));
                $packages->addPackage('files', $app['asset.package_factory']('files'));
                $packages->addPackage('theme', $app['asset.package_factory']('theme'));
                $packages->addPackage('themes', $app['asset.package_factory']('themes'));

                return $packages;
            }
        );

        $app['asset.package.bolt'] = $app->share(
            function ($app) {
                /*
                 * This is technically the wrong directory as our composer script handler
                 * copies the assets to the project's web directory. But since this is
                 * just to check the file's last modified time for versioning it will do fine.
                 */
                $boltViewDir = $app['filesystem']->getDir('bolt://app/view');

                /*
                 * Remove app/view from path as AssetUrl plugin will include it.
                 * This is because "bolt" FS points to bolt's root dir, but
                 * "bolt" asset package points to "bolt_root_dir/app/view".
                 *
                 * This works with composer installs as well.
                 */
                return new Asset\UnprefixedPathPackage(
                    $boltViewDir->getPath() . '/',
                    $app['resources']->getUrl('view', false),
                    $app['asset.version_strategy']($boltViewDir),
                    $app['asset.context']
                );
            }
        );

        $app['asset.package_factory'] = $app->protect(
            function ($name) use ($app) {
                return new PathPackage(
                    $app['resources']->getUrl($name, false),
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
