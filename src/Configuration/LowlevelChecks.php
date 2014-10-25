<?php
namespace Bolt\Configuration;

use Bolt\Library as Lib;
use Bolt\Translation\Translation as Trans;

/**
 * A class to perform several 'low level' checks. Since we're doing it (by design)
 * _before_ the autoloader gets initialized, we can't use autoloading.
 */

class LowlevelChecks
{
    public $config;
    public $disableApacheChecks = false;

    public $checks = array(
        'magicQuotes',
        'safeMode',
        'cache',
        'apache'
    );

    /**
     * The constructor requires a resource manager object to perform checks against.
     * This should ideally be typehinted to Bolt\Configuration\ResourceManager
     *
     * @return void
     **/
    public function __construct($config = null)
    {
        $this->config = $config;
    }


    public function removeCheck($check)
    {
        if (in_array($check, $this->checks)) {
            $this->checks = array_diff($this->checks, array($check));
        }
    }

    public function addCheck($check, $top = false)
    {
        if (!in_array($check, $this->checks)) {
            if ($top) {
                array_unshift($this->checks, $check);
            } else {
                $this->checks[] = $check;
            }
        }
    }

    /**
     * Perform the checks.
     */

    public function doChecks()
    {
        foreach ($this->checks as $check) {
            $method = 'check' . ucfirst($check);
            $this->$method();
        }

        // If the config folder is OK, but the config files are missing, attempt to fix it.
        $this->lowlevelConfigFix('config');
        $this->lowlevelConfigFix('menu');
        $this->lowlevelConfigFix('contenttypes');
        $this->lowlevelConfigFix('taxonomy');
        $this->lowlevelConfigFix('routing');
        $this->lowlevelConfigFix('permissions');

    }

    public function checkMagicQuotes()
    {
        if (get_magic_quotes_gpc()) {
            throw new LowlevelException(
                "Bolt requires 'Magic Quotes' to be <b>off</b>. Please send your hoster to " .
                "<a href='http://www.php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc'>this page</a>, and point out the " .
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that magic_quotes are <u>DEPRECATED</u>. Seriously. <br><br>" .
                "If you can't change it in the server-settings, or your admin won't do it for you, try adding this line to your " .
                "`.htaccess`-file: <pre>php_value magic_quotes_gpc off</pre>"
            );
        }
    }

    public function checkSafeMode()
    {
        if (ini_get('safe_mode')) {
            throw new LowlevelException(
                "Bolt requires 'Safe mode' to be <b>off</b>. Please send your hoster to " .
                "<a href='http://php.net/manual/en/features.safe-mode.php'>this page</a>, and point out the " .
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that safe_mode is <u>DEPRECATED</u>. Seriously."
            );
        }
    }

    private function assertWritableDir($path)
    {
        if (!is_dir($path)) {
            throw new LowlevelException(
                "The folder <code>" . htmlspecialchars($path, ENT_QUOTES) . "</code> doesn't exist. Make sure it is " .
                "present and writable to the user that the webserver is using."
            );
        }
        if (!is_writable($path)) {
            throw new LowlevelException(
                "The folder <code>" . htmlspecialchars($path, ENT_QUOTES) . "</code> isn't writable. Make sure it is " .
                "present and writable to the user that the webserver is using."
            );
        }
    }

    /**
     * Check if the cache dir is present and writable
     */
    public function checkCache()
    {
        $this->assertWritableDir($this->config->getPath('cache'));
    }

    /**
     * This check looks for the presence of the .htaccess file inside the web directory.
     * It is here only as a convenience check for users that install the basic version of Bolt.
     *
     * If you see this error and want to disable it, call $config->getVerifier()->disableApacheChecks();
     * inside your bootstrap.php file, just before the call to $config->verify().
     **/
    public function checkApache()
    {
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
            if (!is_readable($this->config->getPath('web') . '/.htaccess')) {
                throw new LowlevelException(
                    'The file <code>' . htmlspecialchars($this->config->getPath('web'), ENT_QUOTES) . '/.htaccess' .
                    "</code> doesn't exist. Make sure it's present and readable to the user that the " .
                    "webserver is using. " .
                    "If you are not running Apache, or your Apache setup performs the correct rewrites without " .
                    "requiring a .htaccess file (in other words, <strong>if you know what you are doing</strong>), " .
                    'you can disable this check by calling <code>$config->getVerifier()->disableApacheChecks(); ' .
                    "in <code>bootstrap.php</code>"
                );
            }
        }
    }

    /**
     * Perform the check for the database folder. We do this seperately, because it can only
     * be done _after_ the other checks, since we need to have the $config, to see if we even
     * _need_ to do this check.
     */
    public function doDatabaseCheck()
    {
        $cfg = $this->config->app['config']->get('general/database');
        if (!isset($cfg['driver'])) {
            return;
        }

        if ($cfg['driver'] == 'mysql' || $cfg['driver'] == 'postgres' || $cfg['driver'] == 'postgresql') {
            if (empty($cfg['password']) && ($cfg['username'] == "root")) {
                throw new LowlevelException(
                    "There is no <code>password</code> set for the database connection, and you're using user 'root'." .
                    "<br>That must surely be a mistake, right? Bolt will stubbornly refuse to run until you've set a password for 'root'."
                );
            }
            if (empty($cfg['databasename'])) {
                throw new LowlevelException("There is no <code>databasename</code> set for your database.");
            }
            if (empty($cfg['username'])) {
                throw new LowlevelException("There is no <code>username</code> set for your database.");
            }
        }

        if ($cfg['driver'] == 'mysql') {
            if (!extension_loaded('pdo_mysql')) {
                throw new LowlevelException("MySQL was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_mysql driver.");
            }

            return;
        } elseif ($cfg['driver'] == 'postgres' || $cfg['driver'] == 'postgresql') {
            if (!extension_loaded('pdo_pgsql')) {
                throw new LowlevelException("Postgres was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_pgsql driver.");
            }

            return;
        } elseif ($cfg['driver'] == 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                throw new LowlevelException("SQLite was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_sqlite driver.");
            }
        } else {
            throw new LowlevelException('The selected database type is not supported.');
        }

        if (isset($cfg['memory']) && $cfg['memory'] == true) {
            return;
        }

        $filename = isset($cfg['databasename']) ? basename($cfg['databasename']) : 'bolt';
        if (Lib::getExtension($filename) != 'db') {
            $filename .= '.db';
        }

        // Check if the app/database folder and .db file are present and writable
        if (!is_writable($this->config->getPath('database'))) {
            throw new LowlevelException(
                'The folder <code>' .
                $this->config->getPath('database') .
                "</code> doesn't exist or it is not writable. Make sure it's " .
                "present and writable to the user that the webserver is using."
            );
        }

        // If the .db file is present, make sure it is writable
        if (file_exists($this->config->getPath('database') . '/' . $filename) && !is_writable($this->config->getPath('database') . '/' . $filename)) {
            throw new LowlevelException(
                "The database file <code>app/database/" .
                htmlspecialchars($filename, ENT_QUOTES) .
                "</code> isn't writable. Make sure it's " .
                "present and writable to the user that the webserver is using. If the file doesn't exist, make sure the folder is writable and Bolt will create the file."
            );
        }
    }

    public function disableApacheChecks()
    {
        $this->disableApacheChecks = true;
    }

    /**
     * Check if a config file is present and writable. If not, try to create it
     * from the filename.dist.
     *
     * @param string $name Filename stem; .yml extension will be added automatically.
     */
    private function lowlevelConfigFix($name)
    {
        $distname = realpath(__DIR__ . '/../../app/config/' . $name . '.yml.dist');
        $ymlname = realpath($this->config->getPath('config') . '/') . '/' . $name . '.yml';

        if (file_exists($ymlname) && is_readable($ymlname)) {
            return; // Okidoki..
        }

        if (file_exists($ymlname) && !is_readable($ymlname)) {
            $error = sprintf(
                "Couldn't read <code>%s</code>-file inside <code>%s</code>. Make sure the file exists and is readable to the user that the webserver is using.",
                htmlspecialchars($name . ".yml", ENT_QUOTES),
                htmlspecialchars($this->config->getPath('config'), ENT_QUOTES)
            );
            throw new LowlevelException($error);
        }

        if (!@copy($distname, $ymlname)) {
            $message = sprintf(
                "Couldn't create a new <code>%s</code>-file inside <code>%s</code>. Create the file manually by copying
                <code>%s</code>, and optionally make it writable to the user that the webserver is using.",
                htmlspecialchars($name . ".yml", ENT_QUOTES),
                htmlspecialchars($this->config->getPath('config'), ENT_QUOTES),
                htmlspecialchars($name . ".yml.dist", ENT_QUOTES)
            );
            throw new LowlevelException($message);
        }
    }
}
