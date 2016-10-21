<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Configuration\ResourceManager;
use Bolt\Controller;
use Bolt\Controller\ExceptionControllerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Database validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Database implements ValidationInterface, ResourceManagerAwareInterface, ConfigAwareInterface
{
    /** @var ResourceManager */
    private $resourceManager;
    /** @var Config */
    private $config;

    /**
     * {@inheritdoc}
     */
    public function check(ExceptionControllerInterface $exceptionController)
    {
        /** @var Controller\Exception $exceptionController */

        $dbConfig = $this->config->get('general/database');
        $driver = $dbConfig['driver'];

        if ($driver === 'pdo_sqlite') {
            return $this->doDatabaseSqliteCheck($exceptionController, $dbConfig);
        }

        if (!in_array($driver, ['pdo_mysql', 'pdo_pgsql'])) {
            return $exceptionController->databaseDriver('unsupported', null, $driver);
        }

        if ($driver === 'pdo_mysql' && extension_loaded('pdo_mysql') === false) {
            return $exceptionController->databaseDriver('missing', 'MySQL', 'pdo_mysql');
        }

        if ($driver === 'pdo_pgsql' && extension_loaded('pdo_pgsql') === false) {
            return $exceptionController->databaseDriver('missing', 'PostgreSQL', 'pdo_pgsql');
        }

        if (empty($dbConfig['dbname'])) {
            return $exceptionController->databaseDriver('parameter', null, $driver, 'databasename');
        }
        if (empty($dbConfig['user'])) {
            return $exceptionController->databaseDriver('parameter', null, $driver, 'username');
        }
        if (empty($dbConfig['password']) && ($dbConfig['user'] === 'root')) {
            return $exceptionController->databaseDriver('insecure', null, $driver);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isTerminal()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setResourceManager(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    protected function doDatabaseSqliteCheck(Controller\Exception $exceptionController, array $dbConfig)
    {
        if (extension_loaded('pdo_sqlite') === false) {
            return $exceptionController->databaseDriver('missing', 'SQLite', 'pdo_sqlite');
        }

        // If in-memory connection, skip path checks
        if (isset($dbConfig['memory']) && $dbConfig['memory'] === true) {
            return null;
        }

        $fs = new Filesystem();
        $file = $dbConfig['path'];

        // If the file is present, make sure it is writable
        if ($fs->exists($file)) {
            try {
                $fs->touch($file);
            } catch (IOException $e) {
                return $exceptionController->databasePath('file', $file, 'is not writable');
            }

            return null;
        }

        // If the file isn't present, make sure the directory
        // exists and is writable so the file can be created
        $dir = dirname($file);
        if (!$fs->exists($dir)) {
            // At this point, it is possible that the site has been moved and
            // the configured Sqlite database file path is no longer relevant
            // to the site's root path
            $cacheJson = $this->resourceManager->getPath('cache/config-cache.json');
            if ($fs->exists($cacheJson)) {
                $fs->remove($cacheJson);
                $this->config->initialize();

                if (!$fs->exists($dir)) {
                    return $exceptionController->databasePath('folder', $dir, 'does not exist');
                }
            } else {
                return $exceptionController->databasePath('folder', $dir, 'does not exist');
            }
        }

        try {
            $fs->touch($dir);
        } catch (IOException $e) {
            return $exceptionController->databasePath('folder', $dir, 'is not writable');
        }

        return null;
    }
}
