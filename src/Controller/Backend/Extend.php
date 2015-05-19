<?php

namespace Bolt\Controller\Backend;

use Bolt\Exception\PackageManagerException;
use Bolt\Translation\Translator as Trans;
use Silex;
use Silex\ControllerCollection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Extend extends BackendBase
{
    public $readWriteMode;

    /**
     * Returns routes to connect to the application.
     *
     * @param \Silex\ControllerCollection $c
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('', 'overview')
            ->bind('extend');

        $c->get('/check', 'check')
            ->bind('check');

        $c->get('/update', 'update')
            ->bind('update');

        $c->get('/install', 'install')
            ->bind('install');

        $c->get('/uninstall', 'uninstall')
            ->bind('uninstall');

        $c->get('/installed', 'installed')
            ->bind('installed');

        $c->get('/installAll', 'installAll')
            ->bind('installAll');

        $c->get('/installPackage', 'installPackage')
            ->bind('installPackage');

        $c->get('/installInfo', 'installInfo')
            ->bind('installInfo');

        $c->get('/packageInfo', 'packageInfo')
            ->bind('packageInfo');

        $c->get('/generateTheme', 'generateTheme')
            ->bind('generateTheme');
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request            $request
     * @param \Silex\Application $app
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function before(Request $request, Silex\Application $app, $roleRoute = null)
    {
        return parent::before($request, $app, 'extensions');
    }

    public function boot(Silex\Application $app)
    {
    }

    /**
     * Check a package.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function check(Request $request)
    {
        return new JsonResponse($this->app['extend.manager']->checkPackage());
    }

    /**
     * Generate a copy of a theme package.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generateTheme(Request $request)
    {
        $theme = $request->get('theme');
        $newName = $request->get('name');

        if (empty($theme)) {
            return new Response(Trans::__('No theme name found. Theme is not generated.'));
        }

        if (! $newName) {
            $newName = basename($theme);
        }

        $source = $this->app['resources']->getPath('extensions') . '/vendor/' . $theme;
        $destination = $this->app['resources']->getPath('themebase') . '/' . $newName;
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
     * @param Request $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function install(Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');

        $response = $this->app['extend.manager']->requirePackage(
            array(
                'name'    => $package,
                'version' => $version
            )
        );

        if ($response === 0) {
            $this->app['extensions.stats']->recordInstall($package, $version);
            $this->app['logger.system']->info("Installed $package $version", array('event' => 'extensions'));

            return new Response($this->app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($this->app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Install all packages that are in the composer.json but not in vendor.
     *
     * Equivalent to `composer install`
     *
     * @param Request $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function installAll(Request $request)
    {
        $response = $this->app['extend.manager']->installPackages();

        if ($response === 0) {
            return new Response($this->app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($this->app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Get a list of all installed packages.
     *
     * Partially equivalent to `composer show -i`
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installed(Request $request)
    {
        $result = $this->app['extend.manager']->getAllPackages();

        return new JsonResponse($result);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installInfo(Request $request)
    {
        $package = $request->get('package');
        $versions = array('dev' => array(), 'stable' => array());
        $info = $this->app['extend.info']->info($package, $this->app['bolt_version']);
        if (isset($info->version)) {
            foreach ($info->version as $version) {
                $versions[$version->stability][] = $version;
            }
        } else {
            $versions = array('error' => true, 'dev' => array(), 'stable' => array());
        }

        return new JsonResponse($versions);
    }

    /**
     * Package install chooser modal.
     *
     * @param Request $request
     *
     * @return string
     */
    public function installPackage(Request $request)
    {
        return $this->app['render']->render(
            'extend/install-package.twig',
            $this->getRenderContext()
        );
    }

    /**
     * The main 'Extend' page.
     *
     * @param Request $request
     *
     * @return string
     */
    public function overview(Request $request)
    {
        return $this->app['render']->render(
            'extend/extend.twig',
            $this->getRenderContext()
        );
    }

    /**
     * Show installed packages.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function packageInfo(Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');
        $response = $this->app['extend.manager']->showPackage('installed', $package, $version);

        return new JsonResponse($this->app['extend.manager']->formatPackageResponse($response));
    }

    /**
     * Update a package(s).
     *
     * @param Request $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(Request $request)
    {
        $package = $request->get('package') ? $request->get('package') : null;
        $update = $package ? array($package) : array();

        $response = $this->app['extend.manager']->updatePackage($update);

        if ($response === 0) {
            $this->app['logger.system']->info("Updated $package", array('event' => 'extensions'));

            return new JsonResponse($this->app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($this->app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Uninstall a package.
     *
     * @param Request $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function uninstall(Request $request)
    {
        $package = $request->get('package');

        $response = $this->app['extend.manager']->removePackage(array($package));

        if ($response === 0) {
            $this->app['logger.system']->info("Uninstalled $package", array('event' => 'extensions'));

            return new Response($this->app['extend.manager']->getOutput());
        } else {
            throw new PackageManagerException($this->app['extend.manager']->getOutput(), $response);
        }
    }

    /**
     * Get render parameters for Twig.
     *     *
     *
     * @return array
     */
    private function getRenderContext()
    {
        $extensionsPath = $this->app['resources']->getPath('extensions');

        return array(
            'messages'       => $this->app['extend.manager']->messages,
            'enabled'        => $this->app['extend.enabled'],
            'writeable'      => $this->app['extend.writeable'],
            'online'         => $this->app['extend.online'],
            'extensionsPath' => $extensionsPath,
            'site'           => $this->app['extend.site']
        );
    }
}
