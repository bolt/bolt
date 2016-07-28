<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for content relationship data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Relations extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',               'integer', ['autoincrement' => true]);
        $this->table->addColumn('from_contenttype', 'string',  ['length' => 32]);
        $this->table->addColumn('from_id',          'integer', []);
        $this->table->addColumn('to_contenttype',   'string',  ['length' => 32]);
        $this->table->addColumn('to_id',            'integer', []);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['from_contenttype', 'from_id']);
        $this->table->addIndex(['to_contenttype', 'to_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
