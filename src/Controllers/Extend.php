<?php

namespace Bolt\Controllers;

use Bolt\Composer\PackageManager;
use Bolt\Exception\PackageManagerException;
use Bolt\Extensions\ExtensionsInfoService;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Extend implements ControllerProviderInterface, ServiceProviderInterface
{
    public $readWriteMode;

    /**
     * Registers services on the app.
     *
     * @param \Silex\Application $app
     */
    public function register(Silex\Application $app)
    {
        $app['extend.site'] = $app['config']->get('general/extensions/site', 'https://extensions.bolt.cm/');
        $app['extend.repo'] = $app['extend.site'] . 'list.json';
        $app['extend.urls'] = array(
            'list' => 'list.json',
            'info' => 'info.json'
        );

        $app['extend'] = $this;
        $extensionsPath = $app['resources']->getPath('extensions');
        $app['extend.writeable'] = is_dir($extensionsPath) && is_writable($extensionsPath) ? true : false;
        $app['extend.online'] = false;
        $app['extend.enabled'] = $app['config']->get('general/extensions/enabled', true);

        // This exposes the main upload object as a service
        $app['extend.manager'] = $app->share(
            function ($app) {
                return new PackageManager($app);
            }
        );

        $app['extend.info'] = $app->share(
            function ($app) {
                return new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls'], $app['deprecated.php']);
            }
        );
    }

    /**
     * Returns routes to connect to the application.
     *
     * @param \Silex\Application $app An Application instance
     *
     * @return Silex\ControllerCollection A ControllerCollection instance
     */
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $ctr->get('', array($this, 'overview'))
            ->before(array($this, 'before'))
            ->bind('extend');

        $ctr->get('/check', array($this, 'check'))
            ->before(array($this, 'before'))
            ->bind('check');

        $ctr->get('/update', array($this, 'update'))
            ->before(array($this, 'before'))
            ->bind('update');

        $ctr->get('/install', array($this, 'install'))
            ->before(array($this, 'before'))
            ->bind('install');

        $ctr->get('/uninstall', array($this, 'uninstall'))
            ->before(array($this, 'before'))
            ->bind('uninstall');

        $ctr->get('/installed', array($this, 'installed'))
            ->before(array($this, 'before'))
            ->bind('installed');

        $ctr->get('/installAll', array($this, 'installAll'))
            ->before(array($this, 'before'))
            ->bind('installAll');

        $ctr->get('/installPackage', array($this, 'installPackage'))
            ->before(array($this, 'before'))
            ->bind('installPackage');

        $ctr->get('/installInfo', array($this, 'installInfo'))
            ->before(array($this, 'before'))
            ->bind('installInfo');

        $ctr->get('/packageInfo', array($this, 'packageInfo'))
            ->before(array($this, 'before'))
            ->bind('packageInfo');

        $ctr->get('/generateTheme', array($this, 'generateTheme'))
            ->before(array($this, 'before'))
            ->bind('generateTheme');

        return $ctr;
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request            $request
     * @param \Silex\Application $app
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function before(Request $request, Silex\Application $app)
    {
        // This disallows extensions from adding any extra snippets to the output
        if ($request->get("_route") !== 'extend') {
            $app['htmlsnippets'] = false;
        }

        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->isAllowed('extensions')) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to view that page.'));

            return Lib::redirect('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

        return null;
    }

    public function boot(Silex\Application $app)
    {
    }

    /**
     * Check a package.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function check(Silex\Application $app, Request $request)
    {
        return new JsonResponse($app['extend.manager']->checkPackage());
    }

    /**
     * Generate a copy of a theme package.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generateTheme(Silex\Application $app, Request $request)
    {
        $theme = $request->get('theme');
        $newName = $request->get('name');

        if (empty($theme)) {
            return new Response(Trans::__('No theme name found. Theme is not generated.'));
        }

        if (! $newName) {
            $newName = basename($theme);
        }

        $source = $app['resources']->getPath('extensions') . '/vendor/' . $theme;
        $destination = $app['resources']->getPath('themebase') . '/' . $newName;
        if (is_dir($source)) {
            try {
                $filesystem = new Filesystem();
                $filesystem->mkdir($destination);
                $filesystem->mirror($source, $destination);

                if (file_exists($destination . "/config.yml.dist")) {
                    $filesystem->copy($destination . "/config.yml.dist", $destination . "/config.yml");
                }

                return new Response(Trans::__('Theme successfully generated. You can now edit it directly from your theme folder.'));
            } catch (\Exception $e) {
                return new Response(Trans::__('We were unable to generate the theme. It is likely that your theme directory is not writable by Bolt. Check the permissions and try reinstalling.'));
            }
        }

        throw new PackageManagerException("Invalid theme source directory: $source");
    }

    /**
     * Install a package.
     *
     * Equivalent to `composer require author/package`
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function install(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');

        $response = $app['extend.manager']->requirePackage(
            array(
                'name'    => $package,
                'version' => $version
            )
        );

        if ($response === 0) {
            $app['extensions.stats']->recordInstall($package, $version);
            $app['logger.system']->info("Installed $package $version", array('event' => 'extensions'));

            return new Response($app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Install all packages that are in the composer.json but not in vendor.
     *
     * Equivalent to `composer install`
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function installAll(Silex\Application $app, Request $request)
    {
        $response = $app['extend.manager']->installPackages();

        if ($response === 0) {
            return new Response($app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Get a list of all installed packages.
     *
     * Partially equivalent to `composer show -i`
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installed(Silex\Application $app, Request $request)
    {
        $result = $app['extend.manager']->getAllPackages();

        return new JsonResponse($result);
    }

    /**
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installInfo(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $versions = array('dev' => array(), 'stable' => array());
        $info = $app['extend.info']->info($package, $app['bolt_version']);
        if (isset($info->version) && is_array($info->version)) {
            foreach ($info->version as $version) {
                $versions[$version->stability][] = $version;
            }
        }

        return new JsonResponse($versions);
    }

    /**
     * Package install chooser modal.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return string
     */
    public function installPackage(Silex\Application $app, Request $request)
    {
        return $app['render']->render(
            'extend/install-package.twig',
            $this->getRenderContext($app)
        );
    }

    /**
     * The main 'Extend' page.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return string
     */
    public function overview(Silex\Application $app, Request $request)
    {
        return $app['render']->render(
            'extend/extend.twig',
            $this->getRenderContext($app)
        );
    }

    /**
     * Show installed packages.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function packageInfo(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');
        $response = $app['extend.manager']->showPackage('installed', $package, $version);

        return new JsonResponse($app['extend.manager']->formatPackageResponse($response));
    }

    /**
     * Update a package(s).
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(Silex\Application $app, Request $request)
    {
        $package = $request->get('package') ? $request->get('package') : null;
        $update = $package ? array($package) : array();

        $response = $app['extend.manager']->updatePackage($update);

        if ($response === 0) {
            $app['logger.system']->info("Updated $package", array('event' => 'extensions'));

            return new JsonResponse($app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Uninstall a package.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function uninstall(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');

        $response = $app['extend.manager']->removePackage(array($package));

        if ($response === 0) {
            $app['logger.system']->info("Uninstalled $package", array('event' => 'extensions'));

            return new Response($app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Get render parameters for Twig.
     *
     * @param \Silex\Application $app
     *
     * @return array
     */
    private function getRenderContext(Silex\Application $app)
    {
        $extensionsPath = $app['resources']->getPath('extensions');

        return array(
            'messages'       => $app['extend.manager']->messages,
            'enabled'        => $app['extend.enabled'],
            'writeable'      => $app['extend.writeable'],
            'online'         => $app['extend.online'],
            'extensionsPath' => $extensionsPath,
            'site'           => $app['extend.site']
        );
    }
}
