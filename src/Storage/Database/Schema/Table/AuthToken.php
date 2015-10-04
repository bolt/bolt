<?php
namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for authentication token data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AuthToken extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',        'integer',  ['autoincrement' => true]);
        $this->table->addColumn('username',  'string',   ['length' => 32, 'default' => '']);
        $this->table->addColumn('token',     'string',   ['length' => 128]);
        $this->table->addColumn('salt',      'string',   ['length' => 128]);
        $this->table->addColumn('lastseen',  'datetime', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('ip',        'string',   ['length' => 32, 'default' => '']);
        $this->table->addColumn('useragent', 'string',   ['length' => 128, 'default' => '']);
        $this->table->addColumn('validity',  'datetime', ['notnull' => false, 'default' => null]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['username']);
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
            ['column' => 'lastseen', 'property' => 'type'],
            ['column' => 'validity', 'property' => 'type']
        ];
    }
}
