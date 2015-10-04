<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for change log data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogChange extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',            'integer',    ['autoincrement' => true]);
        $this->table->addColumn('date',          'datetime',   []);
        $this->table->addColumn('ownerid',       'integer',    ['notnull' => false]);
        $this->table->addColumn('title',         'string',     ['length' => 256, 'default' => '']);
        $this->table->addColumn('contenttype',   'string',     ['length' => 128]);
        $this->table->addColumn('contentid',     'integer',    []);
        $this->table->addColumn('mutation_type', 'string',     ['length' => 16]);
        $this->table->addColumn('diff',          'json_array', []);
        $this->table->addColumn('comment',       'string',     ['length' => 150, 'default' => '', 'notnull' => false]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['date']);
        $this->table->addIndex(['ownerid']);
        $this->table->addIndex(['contenttype']);
        $this->table->addIndex(['contentid']);
        $this->table->addIndex(['mutation_type']);
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
            ['column' => 'date', 'property' => 'type'],
            ['column' => 'diff', 'property' => 'type']
        ];
    }
}
