<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for fields.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Field extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',           'integer',  ['autoincrement' => true]);
        $this->table->addColumn('contenttype',  'string',   ['length' => 64, 'default' => '']);
        $this->table->addColumn('content_id',   'integer',  []);
        $this->table->addColumn('name',         'string',   ['length' => 128]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['contenttype']);
        $this->table->addIndex(['content_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
