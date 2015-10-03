<?php
namespace Bolt\Storage\Database\Schema\Table;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;

/**
 * Base database table class for Bolt.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class BaseTable
{
    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
    protected $platform;
    /** @var \Doctrine\DBAL\Schema\Table */
    protected $table;
    /** @var string */
    protected $tableName;

    /**
     * Constructor.
     *
     * @param AbstractPlatform $platform
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Get the table's schema object.
     *
     * @param Schema $schema
     * @param string $tableName
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function buildTable(Schema $schema, $tableName)
    {
        $this->table = $schema->createTable($tableName);
        $this->tableName = $this->table->getName();
        $this->addColumns();
        $this->addIndexes();
        $this->setPrimaryKey();

        return $this->table;
    }

    /**
     * Get the table's name.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function getTableName()
    {
        if ($this->tableName === null) {
            throw new \RuntimeException('Table ' . __CLASS__ . ' has not been built yet.');
        }

        return $this->tableName;
    }

    /**
     * A function to return the columns and keys that should be ignored, as DBAL
     * can't seem to do it properly.
     *
     * Returned array format:
     * [
     *     ['column' => '', 'property' => ''],
     *     ['column' => '', 'property' => '']
     * ]
     *
     * @return array|boolean
     */
    public function ignoredChanges()
    {
        return false;
    }

    /**
     * Default value for TEXT fields, differs per platform.
     *
     * @return string|null
     */
    protected function getTextDefault()
    {
        if ($this->platform instanceof SqlitePlatform || $this->platform instanceof PostgreSqlPlatform) {
            return '';
        }

        return null;
    }

    /**
     * Add columns to the table.
     */
    abstract protected function addColumns();

    /**
     * Define the columns that require indexing.
     */
    abstract protected function addIndexes();

    /**
     * Set the table's primary key.
     */
    abstract protected function setPrimaryKey();
}
