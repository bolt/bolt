<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for taxonomy data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Taxonomy extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',           'integer', ['autoincrement' => true]);
        $this->table->addColumn('content_id',   'integer', []);
        $this->table->addColumn('contenttype',  'string',  ['length' => 32]);
        $this->table->addColumn('taxonomytype', 'string',  ['length' => 32]);
        $this->table->addColumn('slug',         'string',  ['length' => 64]);
        $this->table->addColumn('name',         'string',  ['length' => 64, 'default' => '']);
        $this->table->addColumn('sortorder',    'integer', ['default' => 0]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['content_id']);
        $this->table->addIndex(['contenttype']);
        $this->table->addIndex(['taxonomytype']);
        $this->table->addIndex(['slug']);
        $this->table->addIndex(['sortorder']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
