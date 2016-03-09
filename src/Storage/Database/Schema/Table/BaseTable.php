<?php
namespace Bolt\Storage\Database\Schema\Table;

use Bolt\Exception\StorageException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
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
    protected $tablePrefix;
    /** @var string */
    protected $tableName;
    /** @var string */
    protected $aliasName;

    /**
     * Constructor.
     *
     * @param AbstractPlatform $platform
     * @param string           $tablePrefix
     */
    public function __construct(AbstractPlatform $platform, $tablePrefix)
    {
        $this->platform = $platform;
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * Get the table object.
     *
     * @throws StorageException
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function getTable()
    {
        if ($this->table === null) {
            throw new StorageException('Table not built.');
        }

        return $this->table;
    }

    /**
     * Get the table's schema object.
     *
     * @param Schema $schema
     * @param string $aliasName
     * @param string $charset
     * @param string $collate
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function buildTable(Schema $schema, $aliasName, $charset, $collate)
    {
        $tableName = $this->tablePrefix . $aliasName;
        $this->table = $schema->createTable($tableName);
        $this->table->addOption('alias', $aliasName);
        $this->table->addOption('charset', $charset);
        $this->table->addOption('collate', $collate);
        $this->aliasName = $aliasName;
        $this->tableName = $this->table->getName();
        $this->addColumns();
        $this->addIndexes();
        $this->setPrimaryKey();
        $this->addForeignKeyConstraints();

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
     * Get the table's alias (short) name.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function getTableAlias()
    {
        if ($this->aliasName === null) {
            throw new \RuntimeException('Table ' . __CLASS__ . ' has not been built yet.');
        }

        return $this->aliasName;
    }

    /**
     * Default value for TEXT fields, differs per platform.
     *
     * @return string|null
     */
    protected function getTextDefault()
    {
        return '';
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

    /**
     * Set the table's foreign key constraints.
     */
    protected function addForeignKeyConstraints()
    {
    }
}
