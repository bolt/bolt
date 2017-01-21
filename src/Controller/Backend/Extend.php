<?php

namespace Bolt\Controller\Backend;

use Bolt;
use Bolt\Exception\PackageManagerException;
use Bolt\Translation\Translator as Trans;
use Composer\Package\PackageInterface;
use Silex\Application;
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

        $c->get('/depends', 'dependsPackage')
            ->bind('dependsPackage');

        $c->get('/prohibits', 'prohibitsPackage')
            ->bind('prohibitsPackage');

        $c->get('/dumpAutoload', 'dumpAutoload')
            ->bind('dumpAutoload');

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
     * @param Request     $request
     * @param Application $app
     * @param string      $roleRoute
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        return parent::before($request, $app, 'extensions');
    }

    public function boot(Application $app)
    {
    }

    /**
     * Check a package.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function check()
    {
        try {
            return $this->json($this->manager()->checkPackage());
        } catch (PackageManagerException $e) {
            return $this->getJsonException($e);
        }
    }

    /**
     * Find "depends" package dependencies.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function dependsPackage(Request $request)
    {
        $package = $request->get('needle');
        $constraint = $request->get('constraint', '*');

        try {
            $response = $this->manager()->dependsPackage($package, $constraint);
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        return $this->json($response);
    }

    /**
     * Dumps the autoloader.
     *
     * @throws PackageManagerException
     *
     * @return Response
     */
    public function dumpAutoload()
    {
        try {
            $response = $this->manager()->dumpAutoload();
        } catch (PackageManagerException $e) {
            return $this->getJsonException($e);
        }

        if ($response === 0) {
            $this->app['logger.system']->info($this->manager()->getOutput(), ['event' => 'extensions']);

            return new Response($this->manager()->getOutput());
        }

        throw new PackageManagerException($this->manager()->getOutput(), $response);
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
            return new Response(Trans::__('page.extend.theme.generation.missing.name'));
        }

        if (! $newName) {
            $newName = basename($theme);
        }

        $source = $this->resources()->getPath('extensions/vendor/' . $theme);
        $destination = $this->resources()->getPath('themebase/' . $newName);
        if (is_dir($source)) {
            $filesystem = new Filesystem();
            try {
                $filesystem->mkdir($destination);
                $filesystem->mirror($source, $destination);

                if (file_exists($destination . '/config.yml.dist')) {
                    $filesystem->copy($destination . '/config.yml.dist', $destination . '/config.yml');
                }

                return new Response(Trans::__('page.extend.theme.generation.success'));
            } catch (\Exception $e) {
                return new Response(Trans::__('page.extend.theme.generation.failure'));
            }
        }

        return $this->getJsonException(new PackageManagerException(Trans::__('page.extend.message.invalid-theme-source-dir', ['%SOURCE%', $source])));
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
        try {
            $response = $this->manager()->requirePackage(
                [
                    'name'    => $package,
                    'version' => $version,
                ]
            );
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        if ($response === 0) {
            $this->app['extensions.stats']->recordInstall($package, $version);
            $this->app['logger.system']->info("Installed $package $version", ['event' => 'extensions']);

            return new Response($this->manager()->getOutput());
        }

        return $this->getJsonException(new PackageManagerException($this->manager()->getOutput(), $response));
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
        try {
            $response = $this->manager()->installPackages();
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        if ($response === 0) {
            return new Response($this->manager()->getOutput());
        }

        return $this->getJsonException(new PackageManagerException($this->manager()->getOutput(), $response));
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
        try {
            return $this->json($this->manager()->getAllPackages());
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function installInfo(Request $request)
    {
        $package = $request->get('package');
        $versions = ['dev' => [], 'beta' => [], 'RC' => [], 'stable' => []];

        try {
            $info = $this->app['extend.info']->info($package, Bolt\Version::forComposer());
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

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
        try {
            return $this->render('@bolt/extend/_action-modal.twig', $this->getRenderContext());
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }
    }

    /**
     * The main 'Extend' page.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function overview()
    {
        try {
            return $this->render('@bolt/extend/extend.twig', $this->getRenderContext());
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }
    }

    /**
     * Show installed packages.
     *
     * @param Request $request
     *
     * @throws PackageManagerException]
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function packageInfo(Request $request)
    {
        $packageName = $request->get('package');
        $reqVersion = $request->get('version');

        try {
            $response = $this->manager()->showPackage('installed', $packageName, $reqVersion);
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        if (isset($response[$packageName]['package'])) {
            /** @var PackageInterface $package */
            $package = $response[$packageName]['package'];

            return $this->json([
                'name'    => $packageName,
                'version' => $package->getPrettyVersion(),
                'type'    => $package->getType(),
            ]);
        }

        return $this->getJsonException(new PackageManagerException(Trans::__('page.extend.message.package-install-info-fail', ['%PACKAGE%' => $packageName, '%VERSION%' => $reqVersion])));
    }

    /**
     * Find "prohibits" dependencies.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function prohibitsPackage(Request $request)
    {
        $package = $request->get('package');
        $constraint = $request->get('constraint', '*');

        try {
            $response = $this->manager()->prohibitsPackage($package, $constraint);
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        return $this->json($response);
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

        try {
            $response = $this->app['extend.manager']->updatePackage($update);
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        if ($response === 0) {
            $this->app['logger.system']->info("Updated $package", ['event' => 'extensions']);

            return $this->json($this->manager()->getOutput());
        }

        return $this->getJsonException(new PackageManagerException($this->manager()->getOutput(), $response));
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

        try {
            $response = $this->manager()->removePackage([$package]);
        } catch (\Exception $e) {
            return $this->getJsonException($e);
        }

        if ($response === 0) {
            $this->app['logger.system']->info("Uninstalled $package", ['event' => 'extensions']);

            return new Response($this->manager()->getOutput());
        }

        return $this->getJsonException(new PackageManagerException($this->manager()->getOutput(), $response));
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
            'site'           => $this->app['extend.site'],
        ];
    }

    /**
     * @return \Bolt\Composer\PackageManager
     */
    protected function manager()
    {
        return $this->app['extend.manager'];
    }

    /**
     * Return an exception formatted as JSON.
     *
     * @param \Exception $e
     *
     * @return JsonResponse
     */
    private function getJsonException(\Exception $e)
    {
        $error = [
            'error' => [
                'type'    => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ],
        ];

        return new JsonResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
