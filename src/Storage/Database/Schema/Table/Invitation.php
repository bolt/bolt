<?php

namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for invitation codes.
 *
 * @author Carlos Pérez <mrcarlosdev@gmail.com>
 */
class Invitation extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',         'integer',    ['autoincrement' => true]);
        $this->table->addColumn('ownerid',    'integer',    ['notnull' => false]);
        $this->table->addColumn('token',      'string',     ['length' => 128]);
        $this->table->addColumn('expiration', 'datetime',   ['notnull' => false, 'default' => null]);
        $this->table->addColumn('roles',      'json_array', []);
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
