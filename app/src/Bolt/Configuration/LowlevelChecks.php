<?php
namespace Bolt\Configuration;

/**
 * A class to perform several 'low level' checks. Since we're doing it (by design)
 * _before_ the autoloader gets initialized, we can't use autoloading.
 */

class LowlevelChecks
{
    public $config;
    public $disableApacheChecks = false;

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

    /**
     * Checks that the supplied directory has a loadable autoload.php file.
     * This works outside any other config and throws an immediate error if not available.
     */
    public function autoloadCheck($basedir)
    {
        $test = $basedir."/vendor/autoload.php";
        if (!is_readable($test)) {
            $this->lowlevelError(
                "The file <code>vendor/autoload.php</code> doesn't exist. Make sure " .
                "you've installed the required components with Composer."
            );
        }

        return $test;
    }

    /**
     * Perform the checks.
     */
    public function doChecks()
    {
        if (get_magic_quotes_gpc()) {
            $this->lowlevelError(
                "Bolt requires 'Magic Quotes' to be <b>off</b>. Please send your hoster to " .
                "<a href='http://www.php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc'>this page</a>, and point out the ".
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that magic_quotes are <u>DEPRECATED</u>. Seriously. <br><br>" .
                "If you can't change it in the server-settings, or your admin won't do it for you, try adding this line to your " .
                "`.htaccess`-file: <pre>php_value magic_quotes_gpc off</pre>"
            );
        }

        if (ini_get('safe_mode')) {
            $this->lowlevelError(
                "Bolt requires 'Safe mode' to be <b>off</b>. Please send your hoster to " .
                "<a href='http://php.net/manual/en/features.safe-mode.php'>this page</a>, and point out the ".
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that safe_mode is <u>DEPRECATED</u>. Seriously."
            );
        }

        // Check if the cache dir is present and writable
        if (!is_dir($this->config->getPath('cache'))) {
            $this->lowlevelError(
                "The folder <code>" . $this->config->getPath('cache') . "</code> doesn't exist. Make sure it's " .
                "present and writable to the user that the webserver is using."
            );
        } elseif (!is_writable($this->config->getPath('cache'))) {
            $this->lowlevelError(
                "The folder <code>" . $this->config->getPath('cache') . "</code> isn't writable. Make sure it's " .
                "present and writable to the user that the webserver is using."
            );
        }
        
        // Check if there is a writable extension path
        if (!is_dir($this->config->getPath('extensions'))) {
            $this->lowlevelError(
                "The folder <code>" . $this->config->getPath('extensions') . "</code> doesn't exist. Make sure it's " .
                "present and writable to the user that the webserver is using."
            );
        } elseif (!is_writable($this->config->getPath('extensions'))) {
            $this->lowlevelError(
                "The folder <code>" . $this->config->getPath('extensions') . "</code> isn't writable. Make sure it's " .
                "present and writable to the user that the webserver is using."
            );
        }

        /**
         * This check looks for the presence of the .htaccess file inside the web directory.
         * It is here only as a convenience check for users that install the basic version of Bolt.
         *
         * If you see this error and want to disable it, call $config->getVerifier()->disableApacheChecks();
         * inside your bootstrap.php file, just before the call to $config->verify().
         **/
        if (isset($_SERVER['SERVER_SOFTWARE']) && false !== strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') && false === $this->disableApacheChecks) {
            if (!is_readable($this->config->getPath('web').'/.htaccess')) {
                $this->lowlevelError(
                    "The file <code>" . htmlspecialchars($this->config->getPath('web'), ENT_QUOTES) . "/.htaccess".
                    "</code> doesn't exist. Make sure it's present and readable to the user that the " .
                    "webserver is using."
                );
            }
        }

        // If the config folder is OK, but the config files are missing, attempt to fix it.
        $this->lowlevelConfigFix('config');
        $this->lowlevelConfigFix('menu');
        $this->lowlevelConfigFix('contenttypes');
        $this->lowlevelConfigFix('taxonomy');
        $this->lowlevelConfigFix('routing');
        $this->lowlevelConfigFix('permissions');

        // $this->lowlevelError("Done");
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
                $this->lowlevelError(
                    "There is no <code>password</code> set for the database connection, and you're using user 'root'." .
                    "<br>That must surely be a mistake, right? Bolt will stubbornly refuse to run until you've set a password for 'root'."
                );
            }
            if (empty($cfg['databasename'])) {
                $this->lowlevelError("There is no <code>databasename</code> set for your database.");
            }
            if (empty($cfg['username'])) {
                $this->lowlevelError("There is no <code>username</code> set for your database.");
            }
        }

        if ($cfg['driver'] == 'mysql') {
            if (!extension_loaded('pdo_mysql')) {
                $this->lowlevelError("MySQL was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_mysql driver.");
            }

            return;
        } elseif ($cfg['driver'] == 'postgres' || $cfg['driver'] == 'postgresql') {
            if (!extension_loaded('pdo_pgsql')) {
                $this->lowlevelError("Postgres was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_pgsql driver.");
            }

            return;
        } elseif ($cfg['driver'] == 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                $this->lowlevelError("SQLite was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_sqlite driver.");
            }
        } else {
            $this->lowlevelError('The selected database type is not supported.');
        }

        if (isset($cfg['memory']) && true == $cfg['memory']) {
            return;
        }

        $filename = isset($cfg['databasename']) ? basename($cfg['databasename']) : 'bolt';
        if (getExtension($filename) != 'db') {
            $filename .= '.db';
        }

        // Check if the app/database folder and .db file are present and writable
        if (!is_writable($this->config->getPath('database'))) {
            $this->lowlevelError(
                "The folder <code>".
                $this->config->getPath('database') .
                "</code> doesn't exist or it is not writable. Make sure it's " .
                "present and writable to the user that the webserver is using."
            );
        }

        // If the .db file is present, make sure it is writable
        if (file_exists($this->config->getPath('database')."/".$filename) && !is_writable($this->config->getPath('database')."/".$filename)) {
            $this->lowlevelError(
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
        $distname = realpath(__DIR__."/../../../config/$name.yml.dist");
        $ymlname = realpath($this->config->getPath('config')."/") . "/$name.yml";

        if (file_exists($ymlname)) {
            return; // Okidoki..
        }

        if (!@copy($distname, $ymlname)) {
            $message = sprintf(
                "Couldn't create a new <code>%s</code>-file inside <code>%s</code>. Create the file manually by copying
                <code>%s</code>, and optionally make it writable to the user that the webserver is using.",
                htmlspecialchars($name . ".yml", ENT_QUOTES),
                htmlspecialchars($this->config->getPath('config'), ENT_QUOTES),
                htmlspecialchars($name . ".yml.dist", ENT_QUOTES)
            );
            $this->lowlevelError($message);
        }
    }

    /**
     * Print a 'low level' error page, and quit. The user has to fix something.
     *
     * Security caveat: the message is inserted into the page unescaped, so
     * make sure that it contains valid HTML with proper encoding applied.
     *
     * @param string $message
     */
    public function lowlevelError($message)
    {
        $html = <<< EOM
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Bolt - Error</title>
    <style>
        body{font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;color:#333;font-size:14px;line-height:20px;margin:0px;}
        h1 {font-size: 38.5px;line-height: 40px;margin: 10px 0px;}
        p{margin: 0px 0px 10px;}
        strong{font-weight:bold;}
        code, pre {padding: 0px 3px 2px;font-family: Monaco,Menlo,Consolas,"Courier New",monospace;font-size: 12px;color: #333;border-radius: 3px;}
        code {padding: 2px 4px;color: #D14;background-color: #F7F7F9;border: 1px solid #E1E1E8;white-space: nowrap;}
        a {color: #08C;text-decoration: none;}
        ul, ol {padding: 0px;margin: 0px 0px 10px 25px;}
        hr{margin:20px 0;border:0;border-top:1px solid #eeeeee;border-bottom:1px solid #ffffff;}
        .hide{display:none;}
    </style>
</head>
<body style="padding: 20px;">

    <div style="max-width: 530px; margin: auto;">

    <h1>Bolt - Fatal error.</h1>

    <p><strong>%error%</strong></p>

    <p>This is a fatal error. Please fix the error, and refresh the page.
    Bolt can not run, until this error has been corrected. <br>
    Make sure you've read the instructions in the documentation for help. If you
    can't get it to work, post a message on our forum, and we'll try to help you
    out. Be sure to include the exact error message you're getting!</p>

    <ul>
        <li><a href="http://docs.bolt.cm/installation"><span class="hide"> * http://docs.bolt.cm/installation - </span>Bolt documentation - Setup</a></li>
        <li><a href="http://stackoverflow.com/questions/tagged/bolt-cms"><span class="hide"> * http://stackoverflow.com/questions/tagged/bolt-cms - </span>Bolt questions on Stack Overflow</a></li>
    </ul>

    </div>
    <hr>

</body>
</html>
EOM;

        $output = str_replace('%error%', $message, $html);

        // TODO: Information disclosure vulnerability. A misconfigured system
        // will give an attacker detailed information about the state of the
        // system.
        // Suggested solution: in the config file, provide a whitelist of hosts
        // that may access the self-configuration functionality, and only
        // expose the information to hosts on the whitelist.

        // Determine if we're on the Command line. If so, don't output HTML.
        if (php_sapi_name() == 'cli') {
            $output = preg_replace('/<title>.*<\/title>/smi', "", $output); 
            $output = preg_replace('/<style>.*<\/style>/smi', "", $output); 
            $output = strip_tags($output);
            $output = preg_replace('/(\n+)(\s+)/smi', "\n", $output);
        }

        echo $output;

        die();
    }
}
