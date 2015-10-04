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
        $this->table->addColumn('field_id',         'integer',      []);
        $this->table->addColumn('value_varchar',    'string',       ['length' => 255]);
        $this->table->addColumn('value_text',       'text',         ['default' => $this->getTextDefault()]);
        $this->table->addColumn('value_integer',    'integer',      ['default' => 0]);
        $this->table->addColumn('value_float',      'float',        ['default' => 0]);
        $this->table->addColumn('value_decimal',    'decimal',      ['precision' => '18', 'scale' => '9', 'default' => 0]);
        $this->table->addColumn('value_date',       'date',         ['notnull' => false]);
        $this->table->addColumn('value_datetime',   'datetime',     ['notnull' => false]);
        $this->table->addColumn('value_json',       'json_array',   ['notnull' => false]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['field_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function ignoredChanges()
    {
        return [
            ['column' => 'value_date', 'property' => 'type'],
            ['column' => 'value_datetime', 'property' => 'type'],
            ['column' => 'value_json', 'property' => 'type']
        ];
    }
}
