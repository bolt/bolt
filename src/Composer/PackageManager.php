<?php

namespace Bolt\Composer;

use Bolt\Translation\Translator as Trans;
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
    /** @var string Holds the output from Composer\IO\BufferIO */
    private $ioOutput;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        // Set composer environment variables
        putenv('COMPOSER_HOME=' . $this->app['resources']->getPath('cache/composer'));

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
        return $this->ioOutput;
    }

    /**
     * Set the output from the last IO.
     *
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->ioOutput = $output;
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
            $this->updateJson();

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

        try {
            ClientUtils::getDefaultCaBundle();

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
     * Dump fresh autoloader.
     */
    public function dumpautoload()
    {
        $this->app['extend.action']['autoload']->execute();
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
        $this->app['extend.action']['json']->execute($file, $data);
    }

    /**
     * Get packages that a properly installed, pending installed and locally installed.
     *
     * @return array
     */
    public function getAllPackages()
    {
        $packages = [
            'installed' => [],
            'pending'   => [],
            'local'     => []
        ];

        // Installed Composer packages
        $installed = $this->app['extend.action']['show']->execute('installed');
        $packages['installed'] = $this->formatPackageResponse($installed);

        // Pending Composer packages
        $keys = array_keys($installed);
        if ($this->json !== null && !empty($this->json['require'])) {
            foreach ($this->json['require'] as $require => $version) {
                if (!in_array($require, $keys)) {
                    $packages['pending'][] = [
                        'name'     => $require,
                        'version'  => $version,
                        'type'     => 'unknown',
                        'descrip'  => Trans::__('Not yet installed.'),
                        'authors'  => [],
                        'keywords' => []
                    ];
                }
            }
        }

        // Local packages
        foreach ($this->app['extensions']->getEnabled() as $ext) {
            /** @var $ext \Bolt\BaseExtension */
            if ($ext->getInstallType() !== 'local') {
                continue;
            }
            // Get the Composer configuration
            $json = $ext->getComposerJSON();
            if ($json) {
                $packages['local'][] = [
                    'name'     => $json['name'],
                    'title'    => $ext->getName(),
                    'version'  => 'local',
                    'type'     => $json['type'],
                    'descrip'  => $json['description'],
                    'authors'  => $json['authors'],
                    'keywords' => !empty($json['keywords']) ? $json['keywords'] : '',
                    'readme'   => '', // TODO: make local readme links
                    'config'   => $this->linkConfig($json['name'])
                ];
            } else {
                $packages['local'][] = [
                    'title'    => $ext->getName()
                ];
            }
        }

        return $packages;
    }

    /**
     * Format a Composer API package array suitable for AJAX response.
     *
     * @param array $packages
     *
     * @return array
     */
    public function formatPackageResponse(array $packages)
    {
        $pack = [];

        foreach ($packages as $package) {
            /** @var \Composer\Package\CompletePackageInterface $package */
            $package = $package['package'];
            $name = $package->getPrettyName();
            $conf = $this->app['extensions']->getComposerConfig($name);
            $pack[] = [
                'name'     => $name,
                'title'    => $conf['name'],
                'version'  => $package->getPrettyVersion(),
                'authors'  => $package->getAuthors(),
                'type'     => $package->getType(),
                'descrip'  => $package->getDescription(),
                'keywords' => $package->getKeywords(),
                'readme'   => $this->linkReadMe($name),
                'config'   => $this->linkConfig($name)
            ];
        }

        return $pack;
    }

    /**
     * Return the URI for a package's readme.
     *
     * @param string $name
     *
     * @return string
     */
    private function linkReadMe($name)
    {
        $base = $this->app['resources']->getPath('extensionspath/vendor/' . $name);

        $readme = null;
        if (is_readable($base . '/README.md')) {
            $readme = $name . '/README.md';
        } elseif (is_readable($base . '/readme.md')) {
            $readme = $name . '/readme.md';
        }

        if ($readme) {
            return $this->app['resources']->getUrl('async') . 'readme/' . $readme;
        }

        return null;
    }

    /**
     * Return the URI for a package's config file edit window.
     *
     * @param string $name
     *
     * @return string
     */
    private function linkConfig($name)
    {
        // Generate the configfilename from the extension $name
        $configfilename = join('.', array_reverse(explode('/', $name))) . '.yml';

        // Check if we have a config file, and if it's readable. (yet)
        $configfilepath = $this->app['resources']->getPath('extensionsconfig/' . $configfilename);
        if (is_readable($configfilepath)) {
            return $this->app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => 'extensions/' . $configfilename]);
        }

        return null;
    }

    /**
     * Set up Composer JSON file.
     */
    private function updateJson()
    {
        $this->json = $this->app['extend.action']['json']->updateJson();
    }

    /**
     * Ping site to see if we have a valid connection and it is responding correctly.
     *
     * @param boolean $addquery
     */
    private function ping($addquery = false)
    {
        $uri = $this->app['extend.site'] . 'ping';
        $www = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown';

        if ($addquery) {
            $query = [
                'bolt_ver'  => $this->app['bolt_version'],
                'bolt_name' => $this->app['bolt_name'],
                'php'       => phpversion(),
                'www'       => $www
            ];
        } else {
            $query = [];
        }

        try {
            $this->app['guzzle.client']->head($uri, [], ['query' => $query, 'exceptions' => true, 'connect_timeout' => 5]);

            $this->app['extend.online'] = true;
        } catch (ClientException $e) {
            // Thrown for 400 level errors
            $this->messages[] = Trans::__(
                "Client error: %errormessage%",
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        } catch (RequestException $e) {
            // Thrown for connection timeout, DNS errors, etc
            $this->messages[] = Trans::__(
                "Testing connection to extension server failed: %errormessage%",
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        } catch (ServerException $e) {
            // Thrown for 500 level errors
            $this->messages[] = Trans::__(
                "Extension server returned an error: %errormessage%",
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        } catch (\Exception $e) {
            // Catch all
            $this->messages[] = Trans::__(
                "Generic failure while testing connection to extension server: %errormessage%",
                ['%errormessage%' => $e->getMessage()]
            );
            $this->app['extend.online'] = false;
        }
    }
}
