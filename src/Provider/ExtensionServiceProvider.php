<?php

namespace Bolt\Provider;

use Bolt\Composer\Action;
use Bolt\Composer\EventListener\BufferIOListener;
use Bolt\Composer\PackageManager;
use Bolt\Extensions;
use Bolt\Extensions\ExtensionsInfoService;
use Bolt\Extensions\StatService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['extensions'] = $app->share(
            function ($app) {
                $extensions = new Extensions($app);

                return $extensions;
            }
        );

        $app['extensions.stats'] = $app->share(
            function ($app) {
                $stats = new StatService($app);

                return $stats;
            }
        );

        $app['extend.site'] = $app['config']->get('general/extensions/site', 'https://extensions.bolt.cm/');
        $app['extend.repo'] = $app['extend.site'] . 'list.json';
        $app['extend.urls'] = [
            'list' => 'list.json',
            'info' => 'info.json'
        ];

        $app['extend.online'] = false;
        $app['extend.enabled'] = $app['config']->get('general/extensions/enabled', true);
        $app['extend.writeable'] = $app->share(
            function () use ($app) {
                $extensionsPath = $app['resources']->getPath('extensions');

                return is_dir($extensionsPath) && is_writable($extensionsPath) ? true : false;
            }
        );

        $app['extend.manager'] = $app->share(
            function ($app) {
                return new PackageManager($app);
            }
        );

        $app['extend.listener'] = $app->share(
            function ($app) {
                return new BufferIOListener($app['extend.manager']);
            }
        );

        $app['extend.info'] = $app->share(
            function ($app) {
                return new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);
            }
        );

        // Actions
        $app['extend.action'] = $app->share(function (Application $app) {
            return new \Pimple([
                // @codingStandardsIgnoreStart
                'autoload' => $app->share(function () use ($app) { return new Action\DumpAutoload($app); }),
                'check'    => $app->share(function () use ($app) { return new Action\CheckPackage($app); }),
                'install'  => $app->share(function () use ($app) { return new Action\InstallPackage($app); }),
                'json'     => $app->share(function () use ($app) { return new Action\BoltExtendJson($app); }),
                'remove'   => $app->share(function () use ($app) { return new Action\RemovePackage($app); }),
                'require'  => $app->share(function () use ($app) { return new Action\RequirePackage($app); }),
                'search'   => $app->share(function () use ($app) { return new Action\SearchPackage($app); }),
                'show'     => $app->share(function () use ($app) { return new Action\ShowPackage($app); }),
                'update'   => $app->share(function () use ($app) { return new Action\UpdatePackage($app); }),
                // @codingStandardsIgnoreEnd
            ]);
        });

        $app['extend.action.options'] = $app->share(
            function ($app) {
                return [
                    'basedir'                => $app['resources']->getPath('extensions'),
                    'composerjson'           => $app['resources']->getPath('extensions/composer.json'),

                    'dryrun'                 => null,  // dry-run              - Outputs the operations but will not execute anything (implicitly enables --verbose)
                    'verbose'                => true,  // verbose              - Shows more details including new commits pulled in when updating packages
                    'nodev'                  => null,  // no-dev               - Disables installation of require-dev packages
                    'noautoloader'           => null,  // no-autoloader        - Skips autoloader generation
                    'noscripts'              => null,  // no-scripts           - Skips the execution of all scripts defined in composer.json file
                    'withdependencies'       => true,  // with-dependencies    - Add also all dependencies of whitelisted packages to the whitelist
                    'ignoreplatformreqs'     => null,  // ignore-platform-reqs - Ignore platform requirements (php & ext- packages)
                    'preferstable'           => null,  // prefer-stable        - Prefer stable versions of dependencies
                    'preferlowest'           => null,  // prefer-lowest        - Prefer lowest versions of dependencies

                    'sortpackages'           => true,  // sort-packages        - Sorts packages when adding/updating a new dependency
                    'prefersource'           => false, // prefer-source        - Forces installation from package sources when possible, including VCS information
                    'preferdist'             => true,  // prefer-dist          - Forces installation from package dist (archive) even for dev versions
                    'update'                 => true,  // [Custom]             - Do package update as well
                    'noupdate'               => null,  // no-update            - Disables the automatic update of the dependencies
                    'updatenodev'            => true,  // update-no-dev        - Run the dependency update with the --no-dev option
                    'updatewithdependencies' => true,  // update-with-dependencies - Allows inherited dependencies to be updated with explicit dependencies

                    'dev'                    => null,  // dev - Add requirement to require-dev
                                                       //       Removes a package from the require-dev section
                                                       //       Disables autoload-dev rules

                    'onlyname'               => true,  // only-name - Search only in name

                    'optimizeautoloader'     => true,  // optimize-autoloader - Optimizes PSR0 and PSR4 packages to be loaded with classmaps too, good for production.
                ];
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
