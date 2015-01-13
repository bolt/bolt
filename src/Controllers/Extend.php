<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;

use Bolt\Composer\PackageManager;
use Bolt\Exception\BoltComposerException;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;

class Extend implements ControllerProviderInterface, ServiceProviderInterface
{
    public $readWriteMode;

    public function register(Silex\Application $app)
    {
        $app['extend.site'] = $app['config']->get('general/extensions/site', 'https://extensions.bolt.cm/');
        $app['extend.repo'] = $app['extend.site'] . 'list.json';
        $app['extend'] = $this;
        $extensionsPath = $app['resources']->getPath('extensions');
        $app['extend.writeable'] = is_dir($extensionsPath) && is_writable($extensionsPath) ? true : false;
        $app['extend.online'] = false;

        // This exposes the main upload object as a service
        $me = $this;
        $app['extend.manager'] = $app->share(
            function ($app) use ($me) {
                return new PackageManager($app);
            }
        );
    }

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

        $ctr->get('/getLog', array($this, 'getLog'))
            ->before(array($this, 'before'))
            ->bind('getLog');

        $ctr->get('/clearLog', array($this, 'clearLog'))
            ->before(array($this, 'before'))
            ->bind('clearLog');


        return $ctr;
    }

    private function getRenderContext(Silex\Application $app)
    {
        $extensionsPath = $app['resources']->getPath('extensions');

        return array(
                'messages' => $app['extend.manager']->messages,
                'enabled' => $app['extend.writeable'],
                'online' => $app['extend.online'],
                'extensionsPath' => $extensionsPath,
                'site' => $app['extend.site']
        );
    }

    public function overview(Silex\Application $app, Request $request)
    {
        $app['extend.manager']->clearLog();

        return $app['render']->render(
            'extend/extend.twig',
            $this->getRenderContext($app)
        );
    }

    public function installPackage(Silex\Application $app, Request $request)
    {
        return $app['render']->render(
            'extend/install-package.twig',
            $this->getRenderContext($app)
        );
    }

    public function installInfo(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $versions = array('dev' => array(), 'stable' => array());
        try {
            $url = $app['extend.site'] . 'info.json?package=' . $package . '&bolt=' . $app['bolt_version'];
            $info = json_decode(file_get_contents($url));
            foreach ($info->version as $version) {
                $versions[$version->stability][] = $version;
            }
        } catch (\Exception $e) {
            error_log($e); // least we can do
        }

        return new JsonResponse($versions);
    }

    public function packageInfo(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');
        $response = $app['extend.manager']->showPackage('installed', $package, $version);

        return new JsonResponse($app['extend.manager']->formatPackageResponse($response));
    }

    public function check(Silex\Application $app, Request $request)
    {
        return new JsonResponse($app['extend.manager']->check());

    }

    public function update(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');

        $response = Response($app['extend.manager']->update($package));
        if ($response === 0) {
            return new Response($app['extend.manager']->getOutput());
        } else {
            throw new BoltComposerException($app['extend.manager']->getOutput(), $response);
        }
    }

    public function install(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');
        $app['extensions.stats']->recordInstall($package, $version);

        $response = $app['extend.manager']->requirePackage(array(
            'name' => $package,
            'version' => $version
            ));

        if ($response === 0) {
            return new Response($app['extend.manager']->getOutput());
        } else {
            throw new BoltComposerException($app['extend.manager']->getOutput(), $response);
        }
    }

    public function uninstall(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');

        $response = $app['extend.manager']->removePackage(array($package));

        if ($response === 0) {
            return new Response($app['extend.manager']->getOutput());
        } else {
            throw new BoltComposerException($app['extend.manager']->getOutput(), $response);
        }
    }

    public function installed(Silex\Application $app, Request $request)
    {
        $result = $app['extend.manager']->getAllPackages();

        return new JsonResponse($result);
    }

    public function installAll(Silex\Application $app, Request $request)
    {
        return new Response($app['extend.manager']->installAll());
    }

    public function generateTheme(Silex\Application $app, Request $request)
    {
        $theme = $request->get('theme');
        $newName = $request->get('name');

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
    }

    /**
     * Fetch the log and return it.
     */
    public function getLog(Silex\Application $app, Request $request)
    {
        $log = $app['extend.manager']->getLog();
        $log = nl2br($log);

        return new Response($log);
    }

    /**
     * Clear the log and return it.
     */
    public function clearLog(Silex\Application $app, Request $request)
    {
        $app['extend.manager']->clearLog();

        return new Response('');
    }


    /**
     * Middleware function to check whether a user is logged on.
     */
    public function before(Request $request, \Bolt\Application $app)
    {
        // This disallows extensions from adding any extra snippets to the output
        if ($request->get("_route") !== 'extend') {
            $app['htmlsnippets'] = false;
        }

        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->isAllowed('extensions')) {
            $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to view that page.'));

            return Lib::redirect('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');
    }

    public function boot(Silex\Application $app)
    {
    }
}
