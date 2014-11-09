<?php
namespace Bolt\Composer;

use Silex;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Composer\Console\Application as ComposerApp;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\RequestException;
use evidev\composer\Wrapper;
use Bolt\Library as Lib;

class CommandRunner
{
    /**
     * @var \Composer\Console\Application
     */
    public $composerapp;
    public $offline = false;
    public $messages = array();
    public $lastOutput;
    public $packageFile;
    public $installer;
    public $basedir;
    private $cachedir;

    public function __construct(Silex\Application $app, $packageRepo = null, $readWriteMode = false)
    {
        // Needed (for now) to log errors to the bolt_log table.
        $this->app = $app;

        $this->basedir = $app['resources']->getPath('extensions');
        $this->logfile = $app['resources']->getPath('cachepath') . "/composer_log";
        $this->packageRepo = $packageRepo;
        $this->packageFile = $app['resources']->getPath('root') . '/extensions/composer.json';
        $this->installer = $app['resources']->getPath('root') . '/extensions/installer.php';
        $this->cachedir = $this->app['resources']->getPath('cache') . '/composer';

        // Set up composer
        if ($readWriteMode) {
            $this->setup();
            $this->copyInstaller();
        } else {
            $this->offline = true;
        }

    }

    public function check()
    {
        $json = json_decode(file_get_contents($this->packageFile));
        $packages = $json->require;
        $installed = array();
        foreach ($packages as $package => $version) {
            $installed[$package] = $this->execute("show -N -i %s %s", $package, $version);
        }

        $updates = array();
        $installs = array();
        foreach ($installed as $package => $packageInfo) {

            if (is_array($packageInfo)) {
                $response = $this->execute('update --dry-run %s', $package);
                if (!$response) {
                    continue;
                }
                foreach ($response as $resp) {
                    if (strpos($resp, $package) !== false) {
                        $updates[] = $package;
                    }
                }
            } else {
                $installs[] = $package;
            }
        }

        return array('updates' => $updates, 'installs' => $installs);
    }

    public function info($package, $version)
    {
        $check = $this->execute("show -N -i %s %s", $package, $version);

        return $this->showCleanup((array) $check, $package, $version);
    }

    public function update($package = '')
    {
        if (empty($package)) {
            $response = $this->execute("update");
        } else {
            $response = $this->execute("update %s", $package);
        }

        if ($response !== false) {
            return implode($response, '<br>');
        } else {
            $message = 'There was an error updating.';

            return $message;
        }
    }

    public function install($package, $version)
    {
        $response = $this->execute("require %s %s", $package, $version);
        if ($response !== false) {
            return implode('<br>', $response);
        } else {
            $message = 'The requested extension version could not be installed. The most likely reason is that the version' . "\n" .
                'requested is not compatible with this version of Bolt.' . "\n\n" .
                'Check on the extensions site for more information.';

            return $message;
        }
    }

    public function installAll()
    {
        $lockfile = $this->basedir . "/composer.lock";
        if (is_writable($lockfile)) {
            unlink($lockfile);
        }

        $response = $this->execute('install');

        if ($response !== false) {
            return implode($response, '<br>');
        } else {
            $message = 'There was an error during install.';

            return $message;
        }
    }

    public function uninstall($package)
    {
        $json = json_decode(file_get_contents($this->packageFile));
        unset($json->require->$package);
        file_put_contents($this->packageFile, json_encode($json, 128 | 64)); // Used integers to avoid PHP5.3 errors
        $response = $this->execute('update --prefer-dist');
        if ($response) {
            return $package . ' successfully removed';
        } else {
            return $package . ' could not be uninstalled. Try checking that your composer.json file is writable.';
        }
    }

    public function installed()
    {
        $installed = array();

        $json = json_decode(file_get_contents($this->packageFile));
        $packages = $json->require;

        foreach ($packages as $package => $version) {
            $check = $this->execute("show -N -i %s %s", $package, $version);
            $installed[] = $this->showCleanup((array) $check, $package, $version);
        }

        if (!count($installed)) {
            return new JsonResponse(array());
        } else {
            return new JsonResponse($installed);
        }
    }

    /**
     * @param string $format sprintf-style format string.
     * @param string, ... $params one or more parameters to interpolate into the format
     */
    protected function execute()
    {
        $args = func_get_args();
        $format = array_shift($args);
        $sanitize = function ($arg) {
            if (preg_match('/^-/', $arg)) {
                return ''; // starts with a dash: skip
            }
            if (preg_match('#[^a-zA-Z0-9\\-_:/~^\\\\.*]#', $arg)) {
                return ''; // contains invalid characters: skip
            }

            return escapeshellarg($arg);
        };
        $params = array_map($sanitize, $args);
        $command = vsprintf($format, $params);

        // Try to prevent time-outs.
        set_time_limit(0);

        // Tentative fix for the issue on OSX, where the response would be this:
        // [Symfony\Component\Process\Exception\RuntimeException]
        // The process has been signaled with signal "5".
        // @see https://github.com/composer/composer/issues/2146#issuecomment-35478940
        putenv("DYLD_LIBRARY_PATH=''");

        $command .= ' -d ' . $this->basedir . ' -n --no-ansi';
        $this->writeLog('command', $command);

        // Create an InputInterface object to pass to Composer
        $command = new StringInput($command);

        // Create the output buffer
        $output = new BufferedOutput();

        // Execute the Composer task
        $responseCode = $this->composerapp->run($command, $output);

        if ($responseCode == 0) {
            $outputText = $output->fetch();
            $this->writeLog('success', '', $outputText);

            return array_filter(explode("\n", $this->clean($outputText)));
        } else {
            $outputText = $output->fetch();
            $this->writeLog('error', '', $outputText);

            return false;
        }
    }

    /**
     * Output Cleaner:
     * Takes the console output and filters out messages that we don't want to pass on to the user
     *
     * @return void
     **/
    public function clean($output)
    {
        $clean = array(
            "Generating autoload files\n",
            "Loading composer repositories with package information\n",
            "Updating dependencies (including require-dev)\n",
            "Installing dependencies (including require-dev)\n",
            "Installing dependencies (including require-dev) from lock file\n",
            "./composer.json has been updated\n",
            "Writing lock file\n"
        );

        return str_replace($clean, array(), $output);
    }

    protected function showCleanup($output, $name, $version)
    {
        $pack = array();
        foreach ($output as $item) {
            if (strpos($item, ' : ') !== false) {
                $split = explode(' : ', $item);
                $split[0] = str_replace('.', '', $split[0]);
                $pack[trim($split[0])] = trim($split[1]);
                $pack['version'] = $version;
            }
        }
        if (count($pack) < 1) {
            $pack['name'] = $name;
            $pack['version'] = 'unknown';
            $pack['type'] = 'unknown';
            $pack['descrip'] = 'Not yet installed';
        }

        // flatten the composer array one level to make working easier
        $initialized_extensions = array();
        foreach ($this->app['extensions']->composer as $val) {
            $initialized_extensions += $val;
        }

        // For Bolt, we also need to know if the extension has a 'README' and a 'config.yml' file.
        // Note we only do this for successfully loaded extensions.
        if (isset($initialized_extensions[$name])) {
            $paths = $this->app['resources']->getPaths();

            if (is_readable($paths['extensionspath'] . '/vendor/' . $pack['name'] . '/README.md')) {
                $pack['readme'] = $pack['name'] . '/README.md';
            } elseif (is_readable($paths['extensionspath'] . '/vendor/' . $pack['name'] . '/readme.md')) {
                $pack['readme'] = $pack['name'] . '/readme.md';
            }

            if (!empty($pack['readme'])) {
                $pack['readmelink'] = $paths['async'] . 'readme/' . $pack['readme'];
            }

                // generate the configfilename from the extension $name
            $configfilename = join(".", array_reverse(explode("/", $name))) . '.yml';

            // Check if we have a config file, and if it's readable. (yet)
            $configfilepath = $paths['extensionsconfig'] . '/' . $configfilename;
            if (is_readable($configfilepath)) {
                $configfilename = 'extensions/' . $configfilename;
                $pack['config'] = Lib::path('fileedit', array('namespace' => 'config', 'file' => $configfilename));
            }

            // as a bonus we add the extension title to the pack
            $pack['title'] = $initialized_extensions[$name]['name'];
            $pack['authors'] = $initialized_extensions[$name]['json']['authors'];
        }

        return $pack;
    }


    public function clearLog()
    {
        if (is_writable($this->logfile)) {
            unlink($this->logfile);
        }
    }

    public function writeLog($type, $command = '', $output = '')
    {
        // Don't log the 'config' command to prevent noise.
        if (substr($command, 0, 7) == "config ") {
            return;
        }

        $log = "";
        $timestamp = sprintf("<span class='timestamp'>[%s/%s]</span> ", $type, date("H:i:s"));

        if (!empty($command)) {
            $log .= sprintf("%s &gt; <span class='command'>composer %s</span>\n", $timestamp, $command);
        }

        if (!empty($output)) {
            // Perhaps color some output:
            $output = preg_replace('/(\[[a-z]+Exception\])/i', "<span class='error'>$1</span>", $output);
            $output = preg_replace('/(Warning:)/i', "<span class='warning'>$1</span>", $output);

            $log .= sprintf("%s %s\n", $timestamp, $output);
        }

        file_put_contents($this->logfile, $log, FILE_APPEND);

    }

    public function getLog()
    {
        $log = file_get_contents($this->logfile);

        return $log;
    }

    private function setup()
    {
        $httpOk = array(200, 301, 302);

        umask(0000);
        putenv('COMPOSER_HOME=' . $this->app['resources']->getPath('cache') . '/composer');

        // Since we output JSON most of the time, we do _not_ want notices or warnings.
        // Set the error reporting before initializing Composer, to suppress them.
        $oldErrorReporting = error_reporting(E_ERROR);

        // Create the Composer application object
        $this->composerapp = new ComposerApp();

        // Don't automatically exit after a command execution
        $this->composerapp->setAutoExit(false);

        // re-set error reporting to the value it should be.
        error_reporting($oldErrorReporting);

        if (!is_file($this->packageFile)) {
            $this->execute('init');
        }

        if (is_file($this->packageFile) && !is_writable($this->packageFile)) {
            $this->messages[] = sprintf(
                "The file '%s' is not writable. You will not be able to use this feature without changing the permissions.",
                $this->packageFile
            );
        }

        // Ping the extensions server to confirm connection
        $response = $this->ping($this->app['extend.site'], 'ping', true);
        if (! in_array($response, $httpOk)) {
            $this->messages[] = $this->app['extend.site'] . ' is unreachable.';

            $this->offline = true;
        }

        if ($this->offline) {
            $this->messages[] = 'Unable to install/update extensions!';
            return false;
        }

        $this->execute('config repositories.bolt composer ' . $this->app['extend.site'] . 'satis/');
        $jsonfile = file_get_contents($this->packageFile);
        $json = json_decode($jsonfile);
        $json->repositories->packagist = false;
        $json->{'minimum-stability'} = "dev";
        $json->{'prefer-stable'} = true;
        $basePackage = "bolt/bolt";
        $json->provide = new \stdClass();
        $json->provide->$basePackage = $this->app['bolt_version'];
        $json->scripts = array(
            'post-package-install' => "Bolt\\Composer\\ExtensionInstaller::handle",
            'post-package-update' => "Bolt\\Composer\\ExtensionInstaller::handle"
        );

        $pathToWeb = $this->app['resources']->findRelativePath($this->app['resources']->getPath('extensions'), $this->app['resources']->getPath('web'));
        $pathToRoot = $this->app['resources']->findRelativePath($this->app['resources']->getPath('extensions'), $this->app['resources']->getPath('root'));
        $json->extra = array('bolt-web-path' => $pathToWeb);
        $json->autoload = array('files' => array("installer.php"));


        // Write out the file, but only if it's actually changed, and if it's writable.
        if ($jsonfile !== json_encode($json, 128 | 64)) {
            file_put_contents($this->packageFile, json_encode($json, 128 | 64));
        }

        try {
            $json = json_decode((file_get_contents($this->packageRepo)));
            $this->available = $json->packages;
        } catch (\Exception $e) {
            $this->messages[] = sprintf(
                $this->app['translator']->trans("The Bolt extensions Repo at %s is currently unavailable. Check your connection and try again shortly."),
                $this->packageRepo
            );
            $this->available = array();
        }
    }

    private function copyInstaller()
    {
        $class = new \ReflectionClass("Bolt\\Composer\\ExtensionInstaller");
        $filename = $class->getFileName();
        copy($filename, $this->installer);
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
        if ($addquery) {
            $query = array(
                'bolt_ver'  => $this->app['bolt_version'],
                'bolt_name' => $this->app['bolt_name'],
                'php'       => phpversion(),
                'www'       => $_SERVER['SERVER_SOFTWARE']
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
}
