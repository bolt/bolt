<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Configuration\PathResolver;
use Bolt\Exception\Configuration\Validation\Database\DatabaseParameterException;
use Bolt\Exception\Configuration\Validation\Database\InsecureDatabaseException;
use Bolt\Exception\Configuration\Validation\Database\MissingDatabaseExtensionException;
use Bolt\Exception\Configuration\Validation\Database\SqlitePathException;
use Bolt\Exception\Configuration\Validation\Database\UnsupportedDatabaseException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Database validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Database implements ValidationInterface, PathResolverAwareInterface, ConfigAwareInterface
{
    /** @var PathResolver */
    private $pathResolver;
    /** @var Config */
    private $config;

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $dbConfig = $this->config->get('general/database');
        $driver = $dbConfig['driver'];

        if ($driver === 'pdo_sqlite') {
            $this->doDatabaseSqliteCheck($dbConfig);

            return;
        }

        if (!in_array($driver, ['pdo_mysql', 'pdo_pgsql'])) {
            throw new UnsupportedDatabaseException($driver);
        }

        if ($driver === 'pdo_mysql' && extension_loaded('pdo_mysql') === false) {
            throw new MissingDatabaseExtensionException($driver);
        }

        if ($driver === 'pdo_pgsql' && extension_loaded('pdo_pgsql') === false) {
            throw new MissingDatabaseExtensionException($driver);
        }

        if (empty($dbConfig['dbname'])) {
            throw new DatabaseParameterException('databasename', $driver);
        }
        if (empty($dbConfig['user'])) {
            throw new DatabaseParameterException('username', $driver);
        }
        if (empty($dbConfig['password']) && ($dbConfig['user'] === 'root')) {
            throw new InsecureDatabaseException($driver);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setPathResolver(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    protected function doDatabaseSqliteCheck(array $dbConfig)
    {
        if (extension_loaded('pdo_sqlite') === false) {
            throw new MissingDatabaseExtensionException('pdo_sqlite');
        }

        // If in-memory connection, skip path checks
        if (isset($dbConfig['memory']) && $dbConfig['memory'] === true) {
            return;
        }

        $fs = new Filesystem();
        $file = $dbConfig['path'];

        // If the file is present, make sure it is writable
        if ($fs->exists($file)) {
            try {
                $fs->touch($file);
            } catch (IOException $e) {
                throw SqlitePathException::fileNotWritable($file);
            }

            return;
        }

        // If the file isn't present, make sure the directory
        // exists and is writable so the file can be created
        $dir = dirname($file);
        if (!$fs->exists($dir)) {
            // At this point, it is possible that the site has been moved and
            // the configured Sqlite database file path is no longer relevant
            // to the site's root path
            $cacheJson = $this->pathResolver->resolve('%cache%/config-cache.json');
            if ($fs->exists($cacheJson)) {
                $fs->remove($cacheJson);
                $this->config->initialize();

                if (!$fs->exists($dir)) {
                    throw SqlitePathException::folderMissing($dir);
                }
            } else {
                throw SqlitePathException::folderMissing($dir);
            }
        }

        try {
            $fs->touch($dir);
        } catch (IOException $e) {
            throw SqlitePathException::folderNotWritable($dir);
        }
    }
}
