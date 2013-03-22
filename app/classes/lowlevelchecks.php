<?php

/**
 * A class to perform several 'low level' checks. Since we're doing it (by design)
 * _before_ the autoloader gets initialized, we can't use autoloading.
 */
class lowlevelchecks
{

    /**
     * Perform the checks.
     */
    public function doChecks()
    {

        // Bolt requires PHP 5.3.2 or higher.
        if (!checkVersion(PHP_VERSION, "5.3.2")) {
            $this->lowlevelError("Bolt requires PHP <u>5.3.2</u> or higher. You have PHP <u>". PHP_VERSION .
	           "</u>, so Bolt will not run on your current setup.");
        }

        if (get_magic_quotes_gpc()) {
            $this->lowlevelError("Bolt requires 'Magic Quotes' to be <b>off</b>. Please send your hoster to " .
                "<a href='http://www.php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc'>this page</a>, and point out the ".
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that magic_quotes are <u>DEPRECATED</u>. Seriously. <br><br>" .
                "If you can't change it in the server-settings, or your admin won't do it for you, try adding this line to your " .
                "`.htaccess`-file: <pre>php_value magic_quotes_gpc off</pre>"
                );
        }

        if (ini_get('safe_mode')) {
            $this->lowlevelError("Bolt requires Safe mode to be <b>off</b>. Please send your hoster to " .
                "<a href='http://php.net/manual/en/features.safe-mode.php'>this page</a>, and point out the ".
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that safe_mode is <u>DEPRECATED</u>. Seriously.");
        }

        // Check if the vendor folder is present. If not, this is most likely because
        // the user checked out the repo from Git, without running composer.
        if (!file_exists(BOLT_PROJECT_ROOT_DIR.'/vendor/autoload.php')) {
            $this->lowlevelError("The file <code>vendor/autoload.php</code> doesn't exist. Make sure " .
                "you've installed the Silex/Bolt components with Composer.");
        }

        // Check if the app/cache file is present and writable
        if (!file_exists(__DIR__.'/../cache')) {
            $this->lowlevelError("The folder <code>app/cache/</code> doesn't exist. Make sure it's " .
                "present and writable to the user that the webserver is using.");
        }

        if (!is_writable(__DIR__.'/../cache')) {
            $this->lowlevelError("The folder <code>app/cache/</code> isn't writable. Make sure it's " .
                "present and writable to the user that the webserver is using.");
        }

        // Check if .htaccess is present and readable
        if (!is_readable(BOLT_WEB_DIR.'/.htaccess')) {
            $this->lowlevelError("The file <code>" . BOLT_WEB_DIR . "/.htaccess</code> doesn't exist. Make sure it's " .
                "present and readable to the user that the webserver is using.");
        }

        // If the config folder is OK, but the config files are missing, attempt to fix it.
        $this->lowlevelConfigFix('config.yml');
        $this->lowlevelConfigFix('menu.yml');
        $this->lowlevelConfigFix('contenttypes.yml');
        $this->lowlevelConfigFix('taxonomy.yml');

        // $this->lowlevelError("Done");

    }

    /**
     * Perform the check for the database folder. We do this seperately, because it can only
     * be done _after_ the other checks, since we need to have the $config, to see if we even
     * _need_ to do this check.
     */
    public function doDatabaseCheck($config)
    {
        $cfg = $config['general']['database'];

        if($cfg['driver']=='mysql' || $cfg['driver']=='postgres') {
            if(empty($cfg['password'])) {
                $this->lowlevelError("There is no <code>password</code> set for your database. That must surely be a mistake, right?");
            }
            if(empty($cfg['databasename'])) {
                $this->lowlevelError("There is no <code>databasename</code> set for your database.");
            }
            if(empty($cfg['username'])) {
                $this->lowlevelError("There is no <code>username</code> set for your database.");
            }
        }

        if($cfg['driver']=='mysql') {
            if (!extension_loaded('pdo_mysql')) {
                $this->lowlevelError("MySQL was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_mysql driver.");
            }
            return;
        } elseif ($cfg['driver']=='postgres') {
            if (!extension_loaded('pdo_pgsql')) {
                $this->lowlevelError("Postgres was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_pgsql driver.");
            }
            return;
        } elseif ($cfg['driver']=='sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                $this->lowlevelError("SQLite was selected as the database type, but the driver does not exist or is not loaded. Please install the pdo_sqlite driver.");
            }
        } else {
            $this->lowlevelError("The selected database type is not supported.");
        }

        $filename = isset($cfg['databasename']) ? basename($cfg['databasename']) : "bolt";
        if (getExtension($filename)!="db") {
            $filename .= ".db";
        }

        // Check if the app/database folder and .db file are present and writable
        if (!is_writable(__DIR__.'/../database')) {
            $this->lowlevelError("The folder <code>app/database/</code> doesn't exist or it is not writable. Make sure it's " .
                "present and writable to the user that the webserver is using.");
        }

        // If the .db file is present, make sure it is writable
        if (file_exists(__DIR__.'/../database/'.$filename) && !is_writable(__DIR__.'/../database/'.$filename)) {
            $this->lowlevelError("The database file <code>app/database/$filename</code> isn't writable. Make sure it's " .
                "present and writable to the user that the webserver is using. If the file doesn't exist, make sure the folder is writable and Bolt will create the file.");
        }

    }

    /**
     * Check if a config file is present and writable. If not, try to create it
     * from the filename.dist.
     *
     * @param string $name
     */
    private function lowlevelConfigFix($name)
    {
        $distname = realpath(BOLT_CONFIG_DIR."/") . "/" . str_replace(".yml", ".yml.dist", $name);
        $ymlname = realpath(BOLT_CONFIG_DIR."/") . "/" . $name;

        if (file_exists($ymlname)) {
            return; // Okidoki..
        }

        if (!@rename($distname, $ymlname)) {
            $message = sprintf("Couldn't create a new <code>%s</code>-file. Create the file manually by copying
                <code>%s</code>, and optionally make it writable to the user that the webserver is using.",
                $name,
                str_replace(".yml", ".yml.dist", $name)
            );
            $this->lowlevelError($message);
        }

    }

    /**
     * Print a 'low level' error page, and quit. The user has to fix something.
     *
     * @param string $message
     */
    private function lowlevelError($message)
    {

        $paths = getPaths();

        $html = <<< EOM
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Bolt - Error</title>
    <link rel="stylesheet" type="text/css" href="%path%view/css/bootstrap.min.css" />
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
        <li><a href="http://docs.bolt.cm/setup">Bolt documentation - Setup</a></li>
        <li><a href="http://forum.pivotx.net/viewforum.php?f=17">Bolt discussion forum</a></li>
    </ul>

    </div>
    <hr>

</body>
</html>
EOM;

        $html = str_replace("%error%", $message, $html);
        $html = str_replace("%path%", $paths['app'], $html);

        echo $html;

        die();

    }

}
