<?php

namespace Bolt\Controller\Backend;

use Bolt\Exception\PackageManagerException;
use Bolt\Translation\Translator as Trans;
use Silex;
use Silex\ControllerCollection;
use Symfony\Component\Filesystem\Filesystem;
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function check()
    {
        return $this->json($this->manager()->checkPackage());
    }

    /**
     * Generate a copy of a theme package.
     *
     * @param Request $request
     *
     * @throws PackageManagerException
     *
     * @return Response
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

        $source = $this->resources()->getPath('extensions/vendor/' . $theme);
        $destination = $this->resources()->getPath('themebase/' . $newName);
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

        $response = $this->manager()->requirePackage(
            [
                'name'    => $package,
                'version' => $version
            ]
        );

        if ($response === 0) {
            $this->app['extensions.stats']->recordInstall($package, $version);
            $this->app['logger.system']->info("Installed $package $version", ['event' => 'extensions']);

            return new Response($this->manager()->getOutput());
        } else {
            throw new PackageManagerException($this->manager()->getOutput(), $response);
        }
    }

    /**
     * Install all packages that are in the composer.json but not in vendor.
     *
     * Equivalent to `composer install`
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function installAll()
    {
        $response = $this->manager()->installPackages();

        if ($response === 0) {
            return new Response($this->manager()->getOutput());
        } else {
            throw new PackageManagerException($this->manager()->getOutput(), $response);
        }
    }

    /**
     * Get a list of all installed packages.
     *
     * Partially equivalent to `composer show -i`
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installed()
    {
        return $this->json($this->manager()->getAllPackages());
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installInfo(Request $request)
    {
        $package = $request->get('package');
        $versions = ['dev' => [], 'stable' => []];
        $info = $this->app['extend.info']->info($package, $this->app['bolt_version']);

        if (isset($info->version) && is_array($info->version)) {
            foreach ($info->version as $version) {
                $versions[$version->stability][] = $version;
            }
        }

        return $this->json($versions);
    }

    /**
     * Package install chooser modal.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function installPackage()
    {
        return $this->render('@bolt/extend/install-package.twig', $this->getRenderContext());
    }

    /**
     * The main 'Extend' page.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function overview()
    {
        return $this->render('@bolt/extend/extend.twig', $this->getRenderContext());
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
        $response = $this->manager()->showPackage('installed', $package, $version);

        return $this->json($this->manager()->formatPackageResponse($response));
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
        $update = $package ? [$package] : [];

        $response = $this->app['extend.manager']->updatePackage($update);

        if ($response === 0) {
            $this->app['logger.system']->info("Updated $package", ['event' => 'extensions']);

            return $this->json($this->manager()->getOutput());
        } else {
            throw new PackageManagerException($this->manager()->getOutput(), $response);
        }
    }

    /**
     * Uninstall a package.
     *
     * @param Request $request
     *
     * @throws PackageManagerException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uninstall(Request $request)
    {
        $package = $request->get('package');

        $response = $this->manager()->removePackage([$package]);

        if ($response === 0) {
            $this->app['logger.system']->info("Uninstalled $package", ['event' => 'extensions']);

            return new Response($this->manager()->getOutput());
        } else {
            throw new PackageManagerException($this->manager()->getOutput(), $response);
        }
    }

    /**
     * Get render parameters for Twig.
     *
     * @return array
     */
    private function getRenderContext()
    {
        $extensionsPath = $this->resources()->getPath('extensions');

        return [
            'messages'       => $this->app['extend.manager']->getMessages(),
            'enabled'        => $this->app['extend.enabled'],
            'writeable'      => $this->app['extend.writeable'],
            'online'         => $this->app['extend.online'],
            'extensionsPath' => $extensionsPath,
            'site'           => $this->app['extend.site']
        ];
    }

    /**
     * @return \Bolt\Composer\PackageManager
     */
    protected function manager()
    {
        return $this->app['extend.manager'];
    }
}
