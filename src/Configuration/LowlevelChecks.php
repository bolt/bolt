<?php
namespace Bolt\Configuration;

use Bolt\Configuration\Validation\ValidatorInterface;
use Bolt\Controller;
use Bolt\Exception\BootException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated Deprecated since 3.1, to be removed in 4.0.
 */
class LowlevelChecks implements ValidatorInterface
{
    public $config;
    public $disableApacheChecks = false;

    public $checks = [
        'magicQuotes',
        'safeMode',
        'cache',
        'apache',
    ];

    public $configChecks = [
        'config',
        'menu',
        'contenttypes',
        'taxonomy',
        'routing',
        'permissions',
    ];

    public $magicQuotes;
    public $safeMode;
    public $isApache;
    public $mysqlLoaded;
    public $postgresLoaded;
    public $sqliteLoaded;

    /**
     * @param ResourceManager $config
     */
    public function __construct($config = null)
    {
        $this->config = $config;
        $this->magicQuotes = get_magic_quotes_gpc();
        $this->safeMode = ini_get('safe_mode');
        $this->isApache = (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false);
        $this->postgresLoaded = extension_loaded('pdo_pgsql');
        $this->sqliteLoaded = extension_loaded('pdo_sqlite');
        $this->mysqlLoaded = extension_loaded('pdo_mysql');
    }

    /**
     * {@inheritdoc}
     */
    public function check($checkName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function checks()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function add($checkName, $className, $prepend = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function has($checkName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function remove($checkName)
    {
    }

    /**
     * Remove a check from the list causing it to be skipped.
     *
     * @param string $check
     */
    public function removeCheck($check)
    {
        if (in_array($check, $this->checks)) {
            $this->checks = array_diff($this->checks, [$check]);
        }
    }

    /**
     * Add a check
     *
     * @param string  $check
     * @param boolean $top
     */
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
        foreach ($this->configChecks as $check) {
            $this->lowlevelConfigFix($check);
        }
    }

    public function checkMagicQuotes()
    {
        if ($this->magicQuotes) {
            throw new BootException(
                "Bolt requires 'Magic Quotes' to be <b>off</b>. Please send your hoster to " .
                "<a href='http://www.php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc'>this page</a>, and point out the " .
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that magic_quotes are <u>DEPRECATED</u>. Seriously. <br><br>" .
                "If you can't change it in the server-settings, or your admin won't do it for you, try adding this line to your " .
                '`.htaccess`-file: <pre>php_value magic_quotes_gpc off</pre>'
            );
        }
    }

    public function checkSafeMode()
    {
        if (is_string($this->safeMode)) {
            $this->safeMode = $this->safeMode == '1' || strtolower($this->safeMode) === 'on' ? 1 : 0;
        }

        if ($this->safeMode) {
            throw new BootException(
                "Bolt requires 'Safe mode' to be <b>off</b>. Please send your hosting provider to " .
                "<a href='http://php.net/manual/en/features.safe-mode.php'>this page</a>, and point out the " .
                "<span style='color: #F00;'>BIG RED BANNER</span> that states that safe_mode is <u>DEPRECATED</u>. Seriously."
            );
        }
    }

    public function assertWritableDir($path)
    {
        if (!is_dir($path)) {
            throw new BootException(
                'The folder <code>' . htmlspecialchars($path, ENT_QUOTES) . "</code> doesn't exist. Make sure it is " .
                'present and writable to the user that the web server is using.'
            );
        }
        if (!is_writable($path)) {
            throw new BootException(
                'The folder <code>' . htmlspecialchars($path, ENT_QUOTES) . "</code> isn't writable. Make sure it is " .
                'present and writable to the user that the web server is using.'
            );
        }
    }

    /**
     * Check if the cache dir is present and writable.
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
        if ($this->disableApacheChecks) {
            return;
        }
        if ($this->isApache && !is_readable($this->config->getPath('web') . '/.htaccess')) {
            throw new BootException(
                'The file <code>' . htmlspecialchars($this->config->getPath('web'), ENT_QUOTES) . '/.htaccess' .
                "</code> doesn't exist. Make sure it's present and readable to the user that the " .
                'web server is using. ' .
                'If you are not running Apache, or your Apache setup performs the correct rewrites without ' .
                'requiring a .htaccess file (in other words, <strong>if you know what you are doing</strong>), ' .
                'you can disable this check by calling <code>$config->getVerifier()->disableApacheChecks(); ' .
                'in <code>bootstrap.php</code>'
            );
        }
    }

    /**
     * @return Controller\Exception
     */
    private function getExceptionController()
    {
        return $this->config->app['controller.exception'];
    }

    /**
     * Perform the check for the database folder. We do this seperately, because it can only
     * be done _after_ the other checks, since we need to have the $config, to see if we even
     * _need_ to do this check.
     *
     * @return Response|null
     */
    public function doDatabaseCheck()
    {
        $cfg = $this->config->app['config']->get('general/database');
        $driver = $cfg['driver'];

        if ($driver === 'pdo_sqlite') {
            return $this->doDatabaseSqliteCheck($cfg);
        }

        if (!in_array($driver, ['pdo_mysql', 'pdo_pgsql'])) {
            return $this->getExceptionController()->databaseDriver('unsupported', null, $driver);
        }

        if ($driver == 'pdo_mysql' && !$this->mysqlLoaded) {
            return $this->getExceptionController()->databaseDriver('missing', 'MySQL', 'pdo_mysql');
        }

        if ($driver == 'pdo_pgsql' && !$this->postgresLoaded) {
            return $this->getExceptionController()->databaseDriver('missing', 'PostgreSQL', 'pdo_pgsql');
        }

        if (empty($cfg['dbname'])) {
            return $this->getExceptionController()->databaseDriver('parameter', null, $driver, 'databasename');
        }
        if (empty($cfg['user'])) {
            return $this->getExceptionController()->databaseDriver('parameter', null, $driver, 'username');
        }
        if (empty($cfg['password']) && ($cfg['user'] === 'root')) {
            return $this->getExceptionController()->databaseDriver('insecure', null, $driver);
        }

        return null;
    }

    protected function doDatabaseSqliteCheck($config)
    {
        if (!$this->sqliteLoaded) {
            return $this->getExceptionController()->databaseDriver('missing', 'SQLite', 'pdo_sqlite');
        }

        // If in-memory connection, skip path checks
        if (isset($config['memory']) && $config['memory'] === true) {
            return null;
        }

        // If the file is present, make sure it is writable
        $file = $config['path'];
        if (file_exists($file)) {
            if (!is_writable($file)) {
                return $this->getExceptionController()->databasePath('file', $file, 'is not writable');
            }

            return null;
        }

        // If the file isn't present, make sure the directory
        // exists and is writable so the file can be created
        $dir = dirname($file);
        if (!file_exists($dir)) {
            // At this point, it is possible that the site has been moved and
            // the configured Sqlite database file path is no longer relevant
            // to the site's root path
            $cacheJson = $this->config->getPath('cache/config-cache.json');
            if (file_exists($cacheJson)) {
                unlink($cacheJson);
                $this->config->app['config']->initialize();
                $config = $this->config->app['config']->get('general/database');
                if (!file_exists(dirname($config['path']))) {
                    return $this->getExceptionController()->databasePath('folder', $dir, 'does not exist');
                }
            } else {
                return $this->getExceptionController()->databasePath('folder', $dir, 'does not exist');
            }
        }

        if (!is_writable($dir)) {
            return $this->getExceptionController()->databasePath('folder', $dir, 'is not writable');
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
     *
     * @throws \Bolt\Exception\BootException
     */
    protected function lowlevelConfigFix($name)
    {
        $distname = realpath(__DIR__ . '/../../app/config/' . $name . '.yml.dist');
        $ymlname = $this->config->getPath('config') . '/' . $name . '.yml';

        if (file_exists($ymlname) && !is_readable($ymlname)) {
            $error = sprintf(
                "Couldn't read <code>%s</code>-file inside <code>%s</code>. Make sure the file exists and is readable to the user that the web server is using.",
                htmlspecialchars($name . '.yml', ENT_QUOTES),
                htmlspecialchars($this->config->getPath('config'), ENT_QUOTES)
            );
            throw new BootException($error);
        }

        if (!file_exists($ymlname)) {
            // Try and copy from the .dist config file
            try {
                copy($distname, $ymlname);
            } catch (\Exception $e) {
                $message = sprintf(
                    "Couldn't create a new <code>%s</code>-file inside <code>%s</code>. Create the file manually by copying
                    <code>%s</code>, and optionally make it writable to the user that the web server is using.",
                    htmlspecialchars($name . '.yml', ENT_QUOTES),
                    htmlspecialchars($this->config->getPath('config'), ENT_QUOTES),
                    htmlspecialchars($name . '.yml.dist', ENT_QUOTES)
                );

                throw new BootException($message);
            }
        }
    }
}
