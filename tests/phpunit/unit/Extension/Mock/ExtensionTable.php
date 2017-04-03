<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Storage\Database\Schema\Table\BaseTable;

class ExtensionTable extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id',      'integer',  ['autoincrement' => true]);
        $this->table->addColumn('name',    'string',   ['length' => 42]);
        $this->table->addColumn('updated', 'datetime', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['name']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
