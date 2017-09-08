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
        $this->table->addColumn('contenttype',      'string',       ['length' => 64]);
        $this->table->addColumn('content_id',       'integer',      []);
        $this->table->addColumn('name',             'string',       ['length' => 64]);
        $this->table->addColumn('grouping',         'integer',      ['default' => 0]);
        $this->table->addColumn('block',            'string',       ['length' => 64, 'notnull' => false]);
        $this->table->addColumn('fieldname',        'string',       []);
        $this->table->addColumn('fieldtype',        'string',       []);
        $this->table->addColumn('value_string',     'string',       ['length' => 255, 'notnull' => false]);
        $this->table->addColumn('value_text',       'text',         ['notnull' => false]);
        $this->table->addColumn('value_integer',    'integer',      ['notnull' => false]);
        $this->table->addColumn('value_float',      'float',        ['notnull' => false]);
        $this->table->addColumn('value_decimal',    'decimal',      ['precision' => '18', 'scale' => '9', 'notnull' => false]);
        $this->table->addColumn('value_date',       'date',         ['notnull' => false]);
        $this->table->addColumn('value_datetime',   'datetime',     ['notnull' => false]);
        /** @deprecated since 3.3 to be renamed 'value_json' in v4. */
        $this->table->addColumn('value_json_array', 'json',         []);
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
