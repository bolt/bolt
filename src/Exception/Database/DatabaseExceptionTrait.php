<?php

namespace Bolt\Exception\Database;

trait DatabaseExceptionTrait
{
    /** @var string */
    protected $driver;

    protected static $driverNames = [
        'pdo_mysql'   => 'MySQL',
        'mysql'       => 'MySQL',
        'mysql2'      => 'MySQL',
        'mysqli'      => 'MySQL',
        'pdo_sqlite'  => 'SQLite',
        'sqlite'      => 'SQLite',
        'sqlite3'     => 'SQLite',
        'pdo_pgsql'   => 'PostgreSQL',
        'postgres'    => 'PostgreSQL',
        'postgresql'  => 'PostgreSQL',
        'pgsql'       => 'PostgreSQL',
        'pdo_oci'     => 'Oracle',
        'oci8'        => 'OCI8',
        'ibm_db2'     => 'IBM DB2',
        'db2'         => 'IBM DB2',
        'pdo_sqlsrv'  => 'MS SQL Server',
        'sqlsrv'      => 'MS SQL Server',
        'mssql'       => 'MS SQL Server',
        'sqlanywhere' => 'SQL Anywhere',
    ];

    /**
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns the driver's platform (human name).
     *
     * @return string
     */
    public function getPlatform()
    {
        return isset(static::$driverNames[$this->driver]) ? static::$driverNames[$this->driver] : null;
    }
}
