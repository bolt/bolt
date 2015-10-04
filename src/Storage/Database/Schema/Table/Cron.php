<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for cron schedule data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Cron extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',      'integer',  ['autoincrement' => true]);
        $this->table->addColumn('interim', 'string',   ['length' => 16]);
        $this->table->addColumn('lastrun', 'datetime', []);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['interim']);
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
            ['column' => 'lastrun', 'property' => 'type']
        ];
    }
}
