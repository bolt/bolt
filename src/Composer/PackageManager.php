<?php

namespace Bolt\Composer;

use Bolt\Composer\Action\DumpAutoload;
use Bolt\Composer\Action\InitJson;
use Bolt\Composer\Action\RemovePackage;
use Bolt\Composer\Action\RequirePackage;
use Bolt\Composer\Action\SearchPackage;
use Bolt\Composer\Action\ShowPackage;
use Bolt\Composer\Action\UpdatePackage;
use Bolt\Translation\Translator as Trans;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Json\JsonFile;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\RequestException;
use Silex\Application;

class PackageManager
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Composer\IO\BufferIO
     */
    private $io;

    /**
     * @var Composer\Composer
     */
    private $composer;

    /**
     * @var Bolt\Composer\Action\DumpAutoload
     */
    private $dumpautoload;

    /**
     * @var Bolt\Composer\Action\InitJson
     */
    private $initJson;

    /**
     * @var Bolt\Composer\Action\RemovePackage
     */
    private $remove;

    /**
     * @var Bolt\Composer\Action\RequirePackage
     */
    private $require;

    /**
     * @var Bolt\Composer\Action\SearchPackage
     */
    private $search;

    /**
     * @var Bolt\Composer\Action\ShowPackage
     */
    private $show;

    /**
     * @var Bolt\Composer\Action\UpdatePackage
     */
    private $update;

    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @var array
     */
    public $messages = array();

    /**
     *
     * @param Application $app
     * @param boolean     $readWriteMode
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        // Get default options
        $this->getOptions();

        // Set composer environment variables
        putenv('COMPOSER_HOME=' . $this->app['resources']->getPath('cache') . '/composer');

        if ($app['extend.writeable']) {
            // Copy/update installer helper
            $this->copyInstaller();

            // Do required JSON set up
            $this->setupJson();

            // Ping the extensions server to confirm connection
            $response = $this->ping($this->app['extend.site'], 'ping', true);
            $httpOk = array(200, 301, 302);
            if (in_array($response, $httpOk)) {
                $app['extend.online'] = true;
            } else {
                $this->messages[] = $this->app['extend.site'] . ' is unreachable.';
            }
        }

        if ($app['extend.online']) {
            // Set working directory
            chdir($this->options['basedir']);

            // Create the IO
            $this->io = new BufferIO();

            // Create the Composer object
            $this->composer = $this->getComposer();
        }
    }

    /**
     * Get the Composer object
     *
     * @return Composer\Composer
     */
    public function getComposer()
    {
        // Use the factory to get a new Composer object
        $composer = Factory::create($this->io, $this->options['composerjson'], true);
        $repos = $composer->getRepositoryManager()->getRepositories();

        if ($this->app['config']->get('general/extensions/use_http', false)) {
            $this->allowSslDowngrade($repos);
        }

        return $composer;
    }

    /**
     * Return the output from the last IO
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->io->getOutput();
    }

    /**
     * Dump fresh autoloader
     */
    public function dumpautoload()
    {
        if (!$this->dumpautoload) {
            $this->dumpautoload = new DumpAutoload($this->io, $this->composer, $this->options);
        }

        $this->dumpautoload->execute();
    }

    /**
     * Remove packages from the root install
     *
     * @param $packages array Indexed array of package names to remove
     * @return integer 0 on success or a positive error code on failure
     */
    public function removePackage(array $packages)
    {
        if (!$this->remove) {
            $this->remove = new RemovePackage($this->app, $this->io, $this->composer, $this->options);
        }

        // 0 on success or a positive error code on failure
        return $this->remove->execute($packages);
    }

    /**
     * Require (install) packages
     *
     * @param $packages array Associative array of package names/versions to remove
     *                        Format: array('name' => '', 'version' => '')
     * @return integer 0 on success or a positive error code on failure
     */
    public function requirePackage(array $packages)
    {
        if (!$this->require) {
            $this->require = new RequirePackage($this->app, $this->io, $this->composer, $this->options);
        }

        // 0 on success or a positive error code on failure
        return $this->require->execute($packages);
    }

    /**
     * Search for packages
     *
     * @param $packages array Indexed array of package names to search
     * @return array List of matching packages
     */
    public function searchPackage(array $packages)
    {
        if (!$this->search) {
            $this->search = new SearchPackage($this->io, $this->composer, $this->options);
        }

        return $this->search->execute($packages);
    }

    /**
     * Show packages
     *
     * @param $packages
     * @return
     */
    public function showPackage($target, $package = '', $version = '')
    {
        if (!$this->show) {
            $this->show = new ShowPackage($this->io, $this->composer, $this->options);
        }

        return $this->show->execute($target, $package, $version);
    }

    /**
     * Remove packages from the root install
     *
     * @param $packages array Indexed array of package names to remove
     * @return integer 0 on success or a positive error code on failure
     */
    public function updatePackage(array $packages)
    {
        if (!$this->update) {
            $this->update = new UpdatePackage($this->io, $this->composer, $this->options);
        }

        // 0 on success or a positive error code on failure
        return $this->update->execute($packages);
    }

    /**
     * Initialise a new JSON file
     *
     * @param string $file
     * @param array  $data
     */
    public function initJson($file, array $data = array())
    {
        if (!$this->initJson) {
            $this->initJson = new InitJson($this->io, $this->composer, $this->options);
        }

        $this->initJson->execute($file, $data);
    }

    /**
     * Format a Composer API package array suitable for AJAX response
     *
     * @param  array $packages
     * @return array
     */
    public function formatPackageResponse(array $packages) {
        $pack = array();

        foreach ($packages as $package) {
            $pack[] = array(
                'name'       => $package['package']->getPrettyName(),
                'version'    => $package['package']->getPrettyVersion(),
                'authors'    => $package['package']->getAuthors(),
                'type'       => $package['package']->getType(),
                'descrip'    => $package['package']->getDescription(),
                'keywords'   => $package['package']->getKeywords()
            );
        }

        return $pack;
    }

    private function linkReadMe($name)
    {
    }

    private function linkConfig($name)
    {
    }

    /**
     * Install/update extension installer helper
     */
    private function copyInstaller()
    {
        $class = new \ReflectionClass("Bolt\\Composer\\ExtensionInstaller");
        $filename = $class->getFileName();
        copy($filename, $this->options['basedir'] . '/installer.php');
    }

    /**
     * Set up Composer JSON file
     */
    private function setupJson()
    {
        if (!is_file($this->options['composerjson'])) {
            $this->initJson($this->options['composerjson']);
        }

        $jsonFile = new JsonFile($this->options['composerjson']);
        if ($jsonFile->exists()) {
            $json = $jsonorig = $jsonFile->read();
        } else {
            // Error
            $this->messages[] = Trans::__(
                "The Bolt extensions file '%composerjson%' isn't readable.",
                array('%composerjson%' => $this->options['composerjson'])
            );

            $this->app['extend.writeable'] = false;
            $this->app['extend.online'] = false;

            return;
        }

        $pathToWeb = $this->app['resources']->findRelativePath($this->app['resources']->getPath('extensions'), $this->app['resources']->getPath('web'));

        // Enforce standard settings
        $json['repositories']['packagist'] = false;
        $json['repositories']['bolt'] = array(
            'type' => 'composer',
            'url' => $this->app['extend.site'] . 'satis/'
        );
        $json['minimum-stability'] = $this->app['config']->get('general/extensions/stability', 'stable');
        $json['prefer-stable'] = true;
        $json['config'] = array(
            'discard-changes' => true,
            'preferred-install' => 'dist'
        );
        $json['provide']['bolt/bolt'] = $this->app['bolt_version'];
        $json['scripts'] = array(
            'post-package-install' => "Bolt\\Composer\\ExtensionInstaller::handle",
            'post-package-update' => "Bolt\\Composer\\ExtensionInstaller::handle"
        );
        $json['extra'] = array('bolt-web-path' => $pathToWeb);
        $json['autoload'] = array('files' => array('installer.php'));

        // Write out the file, but only if it's actually changed, and if it's writable.
        if ($json != $jsonorig) {
            try {
                umask(0000);
                $jsonFile->write($json);
            } catch (Exception $e) {
                $this->messages[] = Trans::__(
                    'The Bolt extensions Repo at %repository% is currently unavailable. Check your connection and try again shortly.',
                    array('%repository%' => $this->app['extend.site'])
                );
            }
        }
    }

    /**
     * Ping site to see if we have a valid connection and it is responding correctly
     *
     * @param  string        $site
     * @param  string        $uri
     * @param  boolean|array $addquery
     * @return boolean
     */
    private function ping($site, $uri = '', $addquery = false)
    {
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

        $this->guzzleclient = new GuzzleClient($site);

        try {
            $response = $this->guzzleclient->head($uri, null, array('query' => $query))->send();

            return $response->getStatusCode();
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Set repos to allow HTTP instead of HTTPS
     *
     * @param array $repos
     */
    private function allowSslDowngrade(array $repos)
    {
        foreach ($repos as $repo) {
            $reflection = new \ReflectionClass($repo);
            $allowSslDowngrade = $reflection->getProperty('allowSslDowngrade');
            $allowSslDowngrade->setAccessible(true);
            $allowSslDowngrade->setValue($repo, true);
        }
    }

    /**
     * Set the default options
     */
    private function getOptions()
    {
        $this->options = array(
            'basedir'                => $this->app['resources']->getPath('extensions'),
            'composerjson'           => $this->app['resources']->getPath('extensions') . '/composer.json',
            'logfile'                => $this->app['resources']->getPath('cachepath') . '/composer_log',

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
    }
}
