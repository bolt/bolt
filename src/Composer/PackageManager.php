<?php

namespace Bolt\Composer;

use Bolt\Composer\Action\BoltExtendJson;
use Bolt\Composer\Action\CheckPackage;
use Bolt\Composer\Action\DumpAutoload;
use Bolt\Composer\Action\InstallPackage;
use Bolt\Composer\Action\RemovePackage;
use Bolt\Composer\Action\RequirePackage;
use Bolt\Composer\Action\SearchPackage;
use Bolt\Composer\Action\ShowPackage;
use Bolt\Composer\Action\UpdatePackage;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Guzzle\Http\Exception\CurlException as CurlException;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class PackageManager
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var \Bolt\Composer\Action\CheckPackage
     */
    private $check;

    /**
     * @var \Bolt\Composer\Action\DumpAutoload
     */
    private $dumpautoload;

    /**
     * @var \Bolt\Composer\Action\BoltExtendJson
     */
    private $initJson;

    /**
     * @var \Bolt\Composer\Action\InstallPackage
     */
    private $install;

    /**
     * @var \Bolt\Composer\Action\RemovePackage
     */
    private $remove;

    /**
     * @var \Bolt\Composer\Action\RequirePackage
     */
    private $require;

    /**
     * @var \Bolt\Composer\Action\SearchPackage
     */
    private $search;

    /**
     * @var \Bolt\Composer\Action\ShowPackage
     */
    private $show;

    /**
     * @var \Bolt\Composer\Action\UpdatePackage
     */
    private $update;

    /**
     * @var \Bolt\Composer\Factory
     */
    private $factory;

    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @var array|null
     */
    private $json;

    /**
     * @var string[]
     */
    public $messages = array();

    public function __construct(Application $app)
    {
        $this->app = $app;

        // Set composer environment variables
        putenv('COMPOSER_HOME=' . $this->app['resources']->getPath('cache') . '/composer');

        // Set default options
        $this->setOptions();

        // Set up
        $this->setup();
    }

    /**
     * Return/create our Factory object.
     *
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * If the extension project area is writable, ensure the JSON is up-to-date
     * and test connection to the extension server.
     *
     * $app['extend.writeable'] is originally set in Extend::register()
     *
     * If all is OK, set $app['extend.online'] to TRUE
     */
    private function setup()
    {
        if ($this->app['extend.writeable']) {
            // Copy/update installer helper
            $this->copyInstaller();

            // Do required JSON update/set up
            $this->updateJson();

            // Ping the extensions server to confirm connection
            $response = $this->ping(true);

            // @see http://tools.ietf.org/html/rfc2616#section-13.4
            $httpOk = array(200, 203, 206, 300, 301, 302, 307, 410);
            if (in_array($response, $httpOk)) {
                $this->app['extend.online'] = true;
            } else {
                $this->messages[] = $this->app['extend.site'] . ' is unreachable.';
            }
        }

        // Create our Factory
        $this->factory = new Factory($this->app, $this->options);
    }

    /**
     * Get the options.
     *
     * @return string[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get a single option.
     *
     * @param string $key
     *
     * @return string|boolean|null
     */
    public function getOption($key)
    {
        return $this->options[$key];
    }

    /**
     * Get a new Composer object.
     *
     * @return \Composer\Composer
     */
    public function getComposer()
    {
        return $this->factory->getComposer();
    }

    /**
     * Get configured minimum stability.
     *
     * @return string
     */
    public function getMinimumStability()
    {
        return $this->factory->getMinimumStability();
    }

    /**
     * Get a new IO object.
     *
     * @return \Composer\IO\IOInterface
     */
    public function getIO()
    {
        return $this->factory->getIO();
    }

    /**
     * Get a new dependency resolver pool object.
     *
     * @return \Composer\DependencyResolver\Pool
     */
    public function getPool()
    {
        return $this->factory->getPool();
    }

    /**
     * Return the output from the last IO.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->factory->getIO()->getOutput();
    }

    /**
     * Check for packages that need to be installed or updated.
     *
     * @return array
     */
    public function checkPackage()
    {
        if (!$this->check) {
            $this->check = new CheckPackage($this->app);
        }

        return $this->check->execute();
    }

    /**
     * Dump fresh autoloader.
     */
    public function dumpautoload()
    {
        if (!$this->dumpautoload) {
            $this->dumpautoload = new DumpAutoload($this->app);
        }

        $this->dumpautoload->execute();
    }

    /**
     * Install configured packages.
     *
     * @return integer 0 on success or a positive error code on failure
     */
    public function installPackages()
    {
        if (!$this->install) {
            $this->install = new InstallPackage($this->app);
        }

        // 0 on success or a positive error code on failure
        return $this->install->execute();
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
        if (!$this->remove) {
            $this->remove = new RemovePackage($this->app);
        }

        // 0 on success or a positive error code on failure
        return $this->remove->execute($packages);
    }

    /**
     * Require (install) packages.
     *
     * @param $packages array Associative array of package names/versions to remove
     *                        Format: array('name' => '', 'version' => '')
     *
     * @return integer 0 on success or a positive error code on failure
     */
    public function requirePackage(array $packages)
    {
        if (!$this->require) {
            $this->require = new RequirePackage($this->app);
        }

        // 0 on success or a positive error code on failure
        return $this->require->execute($packages);
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
        if (!$this->search) {
            $this->search = new SearchPackage($this->app);
        }

        return $this->search->execute($packages);
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
        if (!$this->show) {
            $this->show = new ShowPackage($this->app);
        }

        return $this->show->execute($target, $package, $version, $root);
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
        if (!$this->update) {
            $this->update = new UpdatePackage($this->app);
        }

        // 0 on success or a positive error code on failure
        return $this->update->execute($packages);
    }

    /**
     * Initialise a new JSON file.
     *
     * @param string $file File to initialise
     * @param array  $data Data to be added as JSON paramter/value pairs
     */
    public function initJson($file, array $data = array())
    {
        if (!$this->initJson) {
            $this->initJson = new BoltExtendJson($this->options);
        }

        $this->initJson->execute($file, $data);
    }

    /**
     * Get packages that a properly installed, pending installed and locally installed.
     *
     * @return array
     */
    public function getAllPackages()
    {
        $packages = array(
            'installed' => array(),
            'pending'   => array(),
            'local'     => array()
        );

        // Installed Composer packages
        $installed = $this->showPackage('installed');
        $packages['installed'] = $this->formatPackageResponse($installed);

        // Pending Composer packages
        $keys = array_keys($installed);
        if ($this->json !== null && !empty($this->json['require'])) {
            foreach ($this->json['require'] as $require => $version) {
                if (!in_array($require, $keys)) {
                    $packages['pending'][] = array(
                        'name'     => $require,
                        'version'  => $version,
                        'type'     => 'unknown',
                        'descrip'  => Trans::__('Not yet installed.'),
                        'authors'  => array(),
                        'keywords' => array()
                    );
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
                $packages['local'][] = array(
                    'name'     => $json['name'],
                    'title'    => $ext->getName(),
                    'type'     => $json['type'],
                    'descrip'  => $json['description'],
                    'authors'  => $json['authors'],
                    'keywords' => !empty($json['keywords']) ? $json['keywords'] : '',
                );
            } else {
                $packages['local'][] = array(
                    'title'    => $ext->getName(),
                );
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
        $pack = array();

        foreach ($packages as $package) {
            /** @var \Composer\Package\CompletePackageInterface $package */
            $package = $package['package'];
            $name = $package->getPrettyName();
            $conf = $this->app['extensions']->getComposerConfig($name);
            $pack[] = array(
                'name'     => $name,
                'title'    => $conf['name'],
                'version'  => $package->getPrettyVersion(),
                'authors'  => $package->getAuthors(),
                'type'     => $package->getType(),
                'descrip'  => $package->getDescription(),
                'keywords' => $package->getKeywords(),
                'readme'   => $this->linkReadMe($name),
                'config'   => $this->linkConfig($name)
            );
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
        $configfilename = join(".", array_reverse(explode("/", $name))) . '.yml';

        // Check if we have a config file, and if it's readable. (yet)
        $configfilepath = $this->app['resources']->getPath('extensionsconfig/' . $configfilename);
        if (is_readable($configfilepath)) {
            return Lib::path('fileedit', array('namespace' => 'config', 'file' => 'extensions/' . $configfilename));
        }

        return null;
    }

    /**
     * Install/update extension installer helper.
     */
    private function copyInstaller()
    {
        $class = new \ReflectionClass("Bolt\\Composer\\ExtensionInstaller");
        $filename = $class->getFileName();
        copy($filename, $this->options['basedir'] . '/ExtensionInstaller.php');
    }

    /**
     * Set up Composer JSON file.
     */
    private function updateJson()
    {
        $initjson = new BoltExtendJson($this->options);
        $this->json = $initjson->updateJson($this->app);
    }

    /**
     * Ping site to see if we have a valid connection and it is responding correctly.
     *
     * @param boolean $addquery
     *
     * @return boolean
     */
    private function ping($addquery = false)
    {
        $uri = $this->app['extend.site'] . 'ping';
        $www = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown';

        if ($addquery) {
            $query = array(
                'bolt_ver'  => $this->app['bolt_version'],
                'bolt_name' => $this->app['bolt_name'],
                'php'       => phpversion(),
                'www'       => $www
            );
        } else {
            $query = array();
        }

        try {
            /** @deprecated remove when PHP 5.3 support is dropped */
            if ($this->app['deprecated.php']) {
                /** @var $response \Guzzle\Http\Message\Response  */
                $response = $this->app['guzzle.client']->head($uri, null, array('query' => $query))->send();
            } else {
                /** @var $reponse \GuzzleHttp\Message\Response */
                $response = $this->app['guzzle.client']->head($uri, array(), array('query' => $query));
            }

            return $response->getStatusCode();
        } catch (CurlException $e) {
            if ($e->getErrorNo() === 58 || $e->getErrorNo() === 60 || $e->getErrorNo() === 77) {
                return $this->setDowngradeSsl($e->getMessage());
            } else {
                $this->messages[] = Trans::__(
                    "cURL experienced an error: %errormessage%",
                    array('%errormessage%' => $e->getMessage())
                );
            }
        } catch (V3RequestException $e) {
            /** @deprecated remove when PHP 5.3 support is dropped */
            $this->messages[] = Trans::__(
                "Testing connection to extension server failed: %errormessage%",
                array('%errormessage%' => $e->getMessage())
            );
        } catch (RequestException $e) {
            $em = $e->getMessage();
            if (strpos($em, 'cURL error 58') === 0 || strpos($em, 'cURL error 60') === 0 || strpos($em, 'cURL error 77') === 0) {
                return $this->setDowngradeSsl($e->getMessage());
            }

            $this->messages[] = Trans::__(
                "Testing connection to extension server failed: %errormessage%",
                array('%errormessage%' => $e->getMessage())
            );
        }

        return false;
    }

    /**
     * Set and notify user that the SSL connection is downgraded.
     *
     * - Eariler versions of libcurl support only SSL, whereas we require TLS.
     * - Later versions of Guzzle use the system's Certificate Authority certificates.
     *
     * In these case, downgrade our Composer to use HTTP
     *
     * @return integer
     */
    private function setDowngradeSsl($err)
    {
        $this->getFactory()->downgradeSsl = true;

        $this->messages[] = Trans::__(
            "System cURL library doesn't support TLS, or the Certificate Authority setup has not been completed (%ERROR%). See http://curl.haxx.se/docs/sslcerts.htmlfor more details. Downgrading to HTTP.",
            array('%ERROR%' => trim($err)));

        return Response::HTTP_OK;
    }

    /**
     * Set the default options.
     */
    private function setOptions()
    {
        $this->options = array(
            'basedir'                => $this->app['resources']->getPath('extensions'),
            'composerjson'           => $this->app['resources']->getPath('extensions') . '/composer.json',

            'dryrun'                 => null,    // dry-run              - Outputs the operations but will not execute anything (implicitly enables --verbose)
            'verbose'                => true,    // verbose              - Shows more details including new commits pulled in when updating packages
            'nodev'                  => null,    // no-dev               - Disables installation of require-dev packages
            'noautoloader'           => null,    // no-autoloader        - Skips autoloader generation
            'noscripts'              => null,    // no-scripts           - Skips the execution of all scripts defined in composer.json file
            'withdependencies'       => true,    // with-dependencies    - Add also all dependencies of whitelisted packages to the whitelist
            'ignoreplatformreqs'     => null,    // ignore-platform-reqs - Ignore platform requirements (php & ext- packages)
            'preferstable'           => null,    // prefer-stable        - Prefer stable versions of dependencies
            'preferlowest'           => null,    // prefer-lowest        - Prefer lowest versions of dependencies

            'sortpackages'           => true,    // sort-packages        - Sorts packages when adding/updating a new dependency
            'prefersource'           => false,   // prefer-source        - Forces installation from package sources when possible, including VCS information
            'preferdist'             => true,    // prefer-dist          - Forces installation from package dist (archive) even for dev versions
            'update'                 => true,    // [Custom]             - Do package update as well
            'noupdate'               => null,    // no-update            - Disables the automatic update of the dependencies
            'updatenodev'            => true,    // update-no-dev        - Run the dependency update with the --no-dev option
            'updatewithdependencies' => true,    // update-with-dependencies - Allows inherited dependencies to be updated with explicit dependencies

            'dev'                    => null,    // dev - Add requirement to require-dev
                                                 //       Removes a package from the require-dev section
                                                 //       Disables autoload-dev rules

            'onlyname'              => true,     // only-name - Search only in name

            'optimizeautoloader'    => true,     // optimize-autoloader - Optimizes PSR0 and PSR4 packages to be loaded with classmaps too, good for production.
        );

        return $this->options;
    }
}
