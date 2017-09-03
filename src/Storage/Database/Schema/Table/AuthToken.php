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
        $this->table->addColumn('user_id',   'integer',  ['notnull' => false]); // Will be made true in v4
        /** @deprecated Deprecated since 3.4, to be removed in v4.0.  */
        $this->table->addColumn('username',  'string',   ['length' => 32, 'notnull' => false]);
        $this->table->addColumn('token',     'string',   ['length' => 128]);
        $this->table->addColumn('salt',      'string',   ['length' => 128]);
        $this->table->addColumn('lastseen',  'datetime', ['notnull' => false]);
        $this->table->addColumn('ip',        'string',   ['length' => 45, 'notnull' => false]);
        $this->table->addColumn('useragent', 'string',   ['length' => 128, 'notnull' => false]);
        $this->table->addColumn('validity',  'datetime', ['notnull' => false]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
