<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for tokens data.
 *
 * @author Carlos Pérez <mrcarlosdev@gmail.com>
 */
class Tokens extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',             'integer',    ['autoincrement' => true]);
        $this->table->addColumn('token',          'string',     ['notnull' => true, 'length' => 128]);
        $this->table->addColumn('expiration',     'datetime',   ['default' => null]);
        $this->table->addColumn('roles',          'json_array', []);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
