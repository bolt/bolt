<?php
namespace Bolt\Database\Table;

class ContentType extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',             'integer',  ['autoincrement' => true]);
        $this->table->addColumn('slug',           'string',   ['length' => 128]);
        $this->table->addColumn('datecreated',    'datetime', []);
        $this->table->addColumn('datechanged',    'datetime', []);
        $this->table->addColumn('datepublish',    'datetime', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('datedepublish',  'datetime', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('templatefields', 'text',     ['default' => '']);
        $this->table->addColumn('username',       'string',   ['length' => 32, 'default' => '', 'notnull' => false]); // We need to keep this around for backward compatibility. For now.
        $this->table->addColumn('ownerid',        'integer',  ['notnull' => false]);
        $this->table->addColumn('status',         'string',   ['length' => 32]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['slug']);
        $this->table->addIndex(['datecreated']);
        $this->table->addIndex(['datechanged']);
        $this->table->addIndex(['datepublish']);
        $this->table->addIndex(['datedepublish']);
        $this->table->addIndex(['status']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
