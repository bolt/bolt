<?php

namespace Bolt\Composer;

use Bolt;
use Bolt\Composer\Package\Dependency;
use Bolt\Composer\Satis\PingService;
use Bolt\Extension\ResolvedExtension;
use Bolt\Filesystem\Exception\ParseException;
use Bolt\Translation\Translator as Trans;
use Composer\CaBundle\CaBundle;
use Composer\Package\CompletePackageInterface;
use Silex\Application;

class PackageManager
{
    /** @var Application */
    protected $app;
    /** @var bool */
    protected $started = false;
    /** @var bool */
    protected $useSsl;

    /** @var array|null */
    private $json;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->setup();
    }

    /**
     * Get the stored messages.
     *
     * @return string[]
     */
    public function getMessages()
    {
        /** @var PingService $pinger */
        $pinger = $this->app['extend.ping'];

        return $pinger->getMessages()->toArray();
    }

    /**
     * Return the output from the last IO.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->app['extend.action.io']->getOutput();
    }

    /**
     * Set up function.
     *
     * - Copy/update the installer event class
     * - Update the composer.json
     * - Test connection to the server
     */
    private function setup()
    {
        if ($this->started) {
            return;
        }

        if ($this->app['extend.writeable']) {
            // Do required JSON update/set up
            try {
                $this->updateJson();
            } catch (ParseException $e) {
                $this->app['logger.flash']->danger(Trans::__('Error reading extensions/composer.json file: %ERROR%', ['%ERROR%' => $e->getMessage()]));
                $this->started = false;

                return;
            }
        }

        $this->started = true;
    }

    /**
     * Check if we can/should use SSL/TLS/HTTP2 or HTTP.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function useSsl()
    {
        if ($this->useSsl !== null) {
            return $this->useSsl;
        }

        if (!extension_loaded('openssl')) {
            return $this->useSsl = false;
        }

        if (!CaBundle::getSystemCaRootBundlePath($this->app['logger.system'])) {
            throw new \Exception('Unable to get system CA bundle, or the bundled Composer CA. Your system is badly misconfigured and there is nothing Bolt can do.');
        }

        return $this->useSsl = true;
    }

    /**
     * Check for packages that need to be installed or updated.
     *
     * @return array
     */
    public function checkPackage()
    {
        return $this->app['extend.action']['check']->execute();
    }

    /**
     * Find which packages cause the given package to be installed.
     *
     * @param string $packageName
     * @param string $constraint
     *
     * @return Dependency[]|null
     */
    public function dependsPackage($packageName, $constraint)
    {
        return $this->app['extend.action']['depends']->execute($packageName, $constraint);
    }

    /**
     * Dump fresh autoloader.
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function dumpAutoload()
    {
        return $this->app['extend.action']['autoload']->execute();
    }

    /**
     * Install configured packages.
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function installPackages()
    {
        return $this->app['extend.action']['install']->execute();
    }

    /**
     * Find which packages prevent the given package from being installed.
     *
     * @param string $packageName
     * @param string $constraint
     *
     * @return Dependency[]|null
     */
    public function prohibitsPackage($packageName, $constraint)
    {
        return $this->app['extend.action']['prohibits']->execute($packageName, $constraint);
    }

    /**
     * Remove packages from the root install.
     *
     * @param $packages array Indexed array of package names to remove
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function removePackage(array $packages)
    {
        return $this->app['extend.action']['remove']->execute($packages);
    }

    /**
     * Require (install) packages.
     *
     * @param $packages array Associative array of package names/versions to remove
     *                        Format: ['name' => '', 'version' => '']
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function requirePackage(array $packages)
    {
        return $this->app['extend.action']['require']->execute($packages);
    }

    /**
     * Search for packages.
     *
     * @param $packages array Indexed array of package names to search
     *
     * @return array List of matching packages
     */
    public function searchPackage(array $packages)
    {
        return $this->app['extend.action']['search']->execute($packages);
    }

    /**
     * Show packages.
     *
     * @param string $target
     * @param string $package
     * @param string $version
     * @param bool   $root
     *
     * @return array
     */
    public function showPackage($target, $package = '', $version = '', $root = false)
    {
        return $this->app['extend.action']['show']->execute($target, $package, $version, $root);
    }

    /**
     * Update packages in the root install.
     *
     * @param  $packages array Indexed array of package names to update
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function updatePackage(array $packages)
    {
        return $this->app['extend.action']['update']->execute($packages);
    }

    /**
     * Initialise a new JSON file.
     *
     * @param string $file File to initialise
     * @param array  $data Data to be added as JSON parameter/value pairs
     */
    public function initJson($file, array $data = [])
    {
        $this->app['extend.manager.json']->init($file, $data);
    }

    /**
     * Get packages that a properly installed, pending installed and locally installed.
     *
     * @return PackageCollection
     */
    public function getAllPackages()
    {
        $collection = new PackageCollection();

        if ($this->started === false) {
            return $collection;
        }

        // Installed
        $installed = $this->app['extend.action']['show']->execute('installed');
        foreach ($installed as $composerPackage) {
            /** @var CompletePackageInterface $composerPackage */
            $composerPackage = $composerPackage['package'];
            $package = Package::createFromComposerPackage($composerPackage);
            $name = $package->getName();
            $extension = $this->app['extensions']->getResolved($name);

            // Handle non-Bolt packages
            if ($extension instanceof ResolvedExtension) {
                $title = $extension->getDisplayName();
                $descriptor = $extension->getDescriptor();
                $constraint = $descriptor ? $descriptor->getConstraint() : null;
                $readme = $this->linkReadMe($extension);
                $config = $this->linkConfig($extension);
                $valid = $extension->isValid();
                $enabled = $extension->isEnabled();
            } else {
                $title = $name;
                $constraint = null;
                $readme = null;
                $config = null;
                $valid = true;
                $enabled = true;
            }

            $package->setStatus('installed');
            $package->setTitle($title);
            $package->setReadmeLink($readme);
            $package->setConfigLink($config);
            $package->setRepositoryLink($composerPackage->getSourceUrl());
            $package->setConstraint($constraint);
            $package->setValid($valid);
            $package->setEnabled($enabled);

            $collection->add($package);
        }

        // Pending
        $requires = isset($this->json['require']) ? $this->json['require'] : [];
        foreach ($requires as $name => $version) {
            if ($collection->get($name)) {
                continue;
            }
            $package = new Package();
            $package->setStatus('pending');
            $package->setName($name);
            $package->setTitle($name);
            $package->setReadmeLink(null);
            $package->setConfigLink(null);
            $package->setVersion($version);
            $package->setType('unknown');
            $package->setDescription(Trans::__('general.phrase.not-installed-yet'));

            $collection->add($package);
        }

        return $collection;
    }

    /**
     * Return the URI for a package's readme.
     *
     * @param ResolvedExtension $extension
     *
     * @return string
     */
    private function linkReadMe(ResolvedExtension $extension)
    {
        return $this->app['url_generator']->generate('readme', [
            'extension' => $extension->getId(),
        ]);
    }

    /**
     * Return the URI for a package's config file edit window.
     *
     * @param ResolvedExtension $extension
     *
     * @return string
     */
    private function linkConfig(ResolvedExtension $extension)
    {
        $file = $this->app['filesystem']->getFile(strtolower("extensions_config://{$extension->getName()}.{$extension->getVendor()}.yml"));
        if ($file->exists()) {
            return $this->app['url_generator']->generate('fileedit', ['namespace' => $file->getMountPoint(), 'file' => $file->getPath()]);
        }

        return null;
    }

    /**
     * Set up Composer JSON file.
     */
    private function updateJson()
    {
        $this->json = $this->app['extend.manager.json']->update();
    }
}
