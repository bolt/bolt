<?php

namespace Bolt\Composer;

use Bolt;
use Bolt\Extension\ResolvedExtension;
use Bolt\Filesystem\Exception\ParseException;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Translation\Translator as Trans;
use Composer\Package\CompletePackageInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Ring\Client\ClientUtils;
use Silex\Application;

class PackageManager
{
    /** @var Application */
    protected $app;
    /** @var boolean */
    protected $started = false;
    /** @var boolean */
    protected $useSsl;

    /** @var array|null  */
    private $json;
    /** @var string[] */
    private $messages = [];

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
        return $this->messages;
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

            // Ping the extensions server to confirm connection
            $this->ping(true);
        }

        $this->started = true;
    }

    /**
     * Check if we can/should use SSL/TLS/HTTP2 or HTTP.
     *
     * @return boolean
     */
    public function useSsl()
    {
        if ($this->useSsl !== null) {
            return $this->useSsl;
        }

        if (!extension_loaded('openssl')) {
            return $this->useSsl = false;
        }

        if (preg_match('{^http:}i', $this->app['extend.site'])) {
            return $this->useSsl = false;
        }

        try {
            if ($this->app['guzzle.api_version'] === 5) {
                ClientUtils::getDefaultCaBundle();
            } else {
                \GuzzleHttp\default_ca_bundle();
            }

            return $this->useSsl = true;
        } catch (\RuntimeException $e) {
            $this->messages[] = $e->getMessage();

            return $this->useSsl = false;
        }
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
     * @return
     */
    public function dependsPackage($packageName, $constraint)
    {
        return $this->app['extend.action']['depends']->execute($packageName, $constraint);
    }

    /**
     * Dump fresh autoloader.
     */
    public function dumpAutoload()
    {
        return $this->app['extend.action']['autoload']->execute();
    }

    /**
     * Install configured packages.
     *
     * @return integer 0 on success or a positive error code on failure
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
     * @return
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
     * @return integer 0 on success or a positive error code on failure
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
     * @return integer 0 on success or a positive error code on failure
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
     * @return integer 0 on success or a positive error code on failure
     */
    public function updatePackage(array $packages)
    {
        return $this->app['extend.action']['update']->execute($packages);
    }

    /**
     * Initialise a new JSON file.
     *
     * @param string $file File to initialise
     * @param array  $data Data to be added as JSON paramter/value pairs
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
            if ($extension) {
                $title = $extension->getDisplayName();
                $constraint = $extension->getDescriptor()->getConstraint() ?: Bolt\Version::VERSION;
                $readme = $this->linkReadMe($extension);
                $config = $this->linkConfig($extension);
                $valid = $extension->isValid();
                $enabled = $extension->isEnabled();
            } else {
                $title = $name;
                $constraint = Bolt\Version::VERSION;
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

        // Local
        $extensions = $this->app['extensions']->all();
        foreach ($extensions as $name => $extension) {
            if ($collection->get($extension->getId())) {
                continue;
            }
            /** @var JsonFile $composerJson */
            $composerJson = $extension->getBaseDirectory()->get('composer.json');
            $package = Package::createFromComposerJson($composerJson->parse());
            $package->setStatus('local');
            $package->setTitle($extension->getDisplayName());
            $package->setReadmeLink($this->linkReadMe($extension));
            $package->setConfigLink($this->linkConfig($extension));
            $package->setValid($extension->isValid());
            $package->setEnabled($extension->isEnabled());

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
        $readme = null;
        $filesystem = $this->app['filesystem']->getFilesystem('extensions');

        if ($filesystem->has(sprintf('%s/README.md', $extension->getRelativePath()))) {
            $readme = $extension->getRelativePath() . '/README.md';
        } elseif ($filesystem->has(sprintf('%s/readme.md', $extension->getRelativePath()))) {
            $readme = $extension->getRelativePath() . '/readme.md';
        }

        if (!$readme) {
            return;
        }

        return $this->app['url_generator']->generate('readme', ['filename' => $readme]);
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
        $configFileName = sprintf('extensions/%s.%s.yml', strtolower($extension->getInnerExtension()->getName()), strtolower($extension->getInnerExtension()->getVendor()));
        if ($this->app['filesystem']->getFilesystem('config')->has($configFileName)) {
            return $this->app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => $configFileName]);
        }

        return;
    }

    /**
     * Set up Composer JSON file.
     */
    private function updateJson()
    {
        $this->json = $this->app['extend.manager.json']->update();
    }

    /**
     * Ping site to see if we have a valid connection and it is responding correctly.
     *
     * @param boolean $addQuery
     */
    private function ping($addQuery = false)
    {
        $uri = $this->app['extend.site'] . 'ping';
        $query = [];
        if ($this->app['request_stack']->getCurrentRequest() !== null) {
            $www = $this->app['request_stack']->getCurrentRequest()->server->get('SERVER_SOFTWARE', 'unknown');
        } else {
            $www = 'unknown';
        }
        if ($addQuery) {
            $query = [
                'bolt_ver'  => Bolt\Version::VERSION,
                'php'       => phpversion(),
                'www'       => $www,
            ];
        }

        try {
            $this->app['guzzle.client']->head($uri, ['query' => $query, 'exceptions' => true, 'connect_timeout' => 10, 'timeout' => 30]);

            $this->app['extend.online'] = true;
        } catch (ClientException $e) {
            // Thrown for 400 level errors
            $this->messages[] = Trans::__(
                'Client error: %errormessage%',
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        } catch (ServerException $e) {
            // Thrown for 500 level errors
            $this->messages[] = Trans::__(
                'Extension server returned an error: %errormessage%',
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        } catch (RequestException $e) {
            // Thrown for connection timeout, DNS errors, etc
            $this->messages[] = Trans::__(
                'Testing connection to extension server failed: %errormessage%',
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        } catch (\Exception $e) {
            // Catch all
            $this->messages[] = Trans::__(
                'Generic failure while testing connection to extension server: %errormessage%',
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        }
    }
}
