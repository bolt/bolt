<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for field values with separate columns for each data type.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FieldValue extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',               'integer',      ['autoincrement' => true]);
        $this->table->addColumn('contenttype',      'string',       ['length' => 64, 'default' => '']);
        $this->table->addColumn('content_id',       'integer',      []);
        $this->table->addColumn('name',             'string',       ['length' => 64, 'default' => '']);
        $this->table->addColumn('grouping',         'integer',      ['default' => 0]);
        $this->table->addColumn('fieldname',        'string',       []);
        $this->table->addColumn('fieldtype',        'string',       []);
        $this->table->addColumn('value_string',     'string',       ['length' => 255, 'notnull' => false]);
        $this->table->addColumn('value_text',       'text',         ['default' => $this->getTextDefault(), 'notnull' => false]);
        $this->table->addColumn('value_integer',    'integer',      ['notnull' => false]);
        $this->table->addColumn('value_float',      'float',        ['notnull' => false]);
        $this->table->addColumn('value_decimal',    'decimal',      ['precision' => '18', 'scale' => '9', 'notnull' => false]);
        $this->table->addColumn('value_date',       'date',         ['notnull' => false]);
        $this->table->addColumn('value_datetime',   'datetime',     ['notnull' => false]);
        $this->table->addColumn('value_json_array', 'json_array',   []);
        $this->table->addColumn('value_boolean',    'boolean',      ['default' => 0]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['content_id', 'contenttype']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
