<?php
namespace Bolt\Composer;

use Silex;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommandRunner
{
    public $wrapper;
    public $messages = array();
    public $lastOutput;
    public $packageFile;
    public $basedir;

    public function __construct(Silex\Application $app, $packageRepo = null)
    {
        // Needed (for now) to log errors to the bolt_log table.
        $this->app = $app;

        $this->basedir = $app['resources']->getPath('extensions');
        $this->logfile = $app['resources']->getPath('cachepath') . "/composer_log";
        $this->packageRepo = $packageRepo;
        $this->packageFile = $app['resources']->getPath('root') . '/extensions/composer.json';

        // Set up composer
        $this->setup();
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
        file_put_contents($this->packageFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
            return new JsonResponse([]);
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

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $responseCode = $this->wrapper->run($command, $output);

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
        foreach($this->app['extensions']->composer as $val) {
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
            $configfilename = join(".", array_reverse(explode("/", $name))). '.yml';

            // Check if we have a config file, and if it's readable. (yet)
            $configfilepath = $paths['extensionsconfig'] . '/' . $configfilename;
            if (is_readable($configfilepath)) {
                $configfilename = 'extensions/' . $configfilename;
                $pack['config'] = path('fileedit', array('namespace' => 'config', 'file' => $configfilename));
            }
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
        $timestamp = sprintf("<span class='timestamp'>[%s]</span> ", date("H:i:s"));

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
        umask(0000);
        putenv('COMPOSER_HOME=' . $this->app['resources']->getPath('cache') . '/composer');

        // Since we output JSON most of the time, we do _not_ want notices or warnings.
        // Set the error reporting before initializing the wrapper, to suppress them.
        $oldErrorReporting = error_reporting(E_ERROR);

        $this->wrapper = \evidev\composer\Wrapper::create();

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

        $this->execute('config repositories.bolt composer ' . $this->app['extend.site'] . 'satis/');
        $jsonfile = file_get_contents($this->packageFile);
        $json = json_decode($jsonfile);
        $json->repositories->packagist = false;
        $json->{'minimum-stability'} = "dev";
        $json->{'prefer-stable'} = true;
        $basePackage = "bolt/bolt";
        $json->provide = new \stdClass();
        $json->provide->$basePackage = $this->app['bolt_version'];
        // $json->scripts = array(
        //     'post-package-install' => "Bolt\\Composer\\ScriptHandler::extensions",
        //     'post-package-update' => "Bolt\\Composer\\ScriptHandler::extensions"
        // );
        // $json->autoload = array(
        //     "files"=> array($app['resources']->getPath('root')."/vendor/autoload.php")
        // );
        $pathToWeb = $this->app['resources']->findRelativePath($this->app['resources']->getPath('extensions'), $this->app['resources']->getPath('web'));
        $json->extra = array('bolt-web-path' => $pathToWeb);

        // Write out the file, but only if it's actually changed, and if it's writable.
        if ($jsonfile !== json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) {
            file_put_contents($this->packageFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
}
