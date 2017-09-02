<?php

namespace Bolt\Storage\Database\Schema\Table;

/**
 * Table for user account data.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Users extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',             'integer',    ['autoincrement' => true]);
        $this->table->addColumn('username',       'string',     ['length' => 32]);
        $this->table->addColumn('password',       'string',     ['length' => 128]);
        $this->table->addColumn('email',          'string',     ['length' => 254]);
        $this->table->addColumn('lastseen',       'datetime',   ['notnull' => false]);
        $this->table->addColumn('lastip',         'string',     ['length' => 45, 'notnull' => false]);
        $this->table->addColumn('displayname',    'string',     ['length' => 32]);
        $this->table->addColumn('stack',          'json',       []);
        $this->table->addColumn('enabled',        'boolean',    ['default' => true]);
        $this->table->addColumn('shadowpassword', 'string',     ['length' => 128, 'notnull' => false]);
        $this->table->addColumn('shadowtoken',    'string',     ['length' => 128, 'notnull' => false]);
        $this->table->addColumn('shadowvalidity', 'datetime',   ['notnull' => false]);
        $this->table->addColumn('failedlogins',   'integer',    ['default' => 0]);
        $this->table->addColumn('throttleduntil', 'datetime',   ['notnull' => false]);
        $this->table->addColumn('roles',          'json',       []);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['username']);
        $this->table->addUniqueIndex(['email']);

        $this->table->addIndex(['enabled']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
