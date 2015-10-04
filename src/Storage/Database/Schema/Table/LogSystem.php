<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for system logging data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogSystem extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',         'integer',    ['autoincrement' => true]);
        $this->table->addColumn('level',      'integer',    []);
        $this->table->addColumn('date',       'datetime',   []);
        $this->table->addColumn('message',    'string',     ['length' => 1024]);
        $this->table->addColumn('ownerid',    'integer',    ['notnull' => false]);
        $this->table->addColumn('requesturi', 'string',     ['length' => 128]);
        $this->table->addColumn('route',      'string',     ['length' => 128]);
        $this->table->addColumn('ip',         'string',     ['length' => 32, 'default' => '']);
        $this->table->addColumn('context',    'string',     ['length' => 32]);
        $this->table->addColumn('source',     'json_array', []);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['level']);
        $this->table->addIndex(['date']);
        $this->table->addIndex(['ownerid']);
        $this->table->addIndex(['context']);
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
            ['column' => 'source', 'property' => 'type']
        ];
    }
}
