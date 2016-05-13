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
        $this->table->addColumn('lastseen',       'datetime',   ['notnull' => false, 'default' => null]);
        $this->table->addColumn('lastip',         'string',     ['length' => 45, 'default' => '']);
        $this->table->addColumn('displayname',    'string',     ['length' => 32]);
        $this->table->addColumn('stack',          'json_array', []);
        $this->table->addColumn('enabled',        'boolean',    ['default' => true]);
        $this->table->addColumn('shadowpassword', 'string',     ['length' => 128, 'notnull' => false, 'default' => null]);
        $this->table->addColumn('shadowtoken',    'string',     ['length' => 128, 'notnull' => false, 'default' => null]);
        $this->table->addColumn('shadowvalidity', 'datetime',   ['notnull' => false, 'default' => null]);
        $this->table->addColumn('failedlogins',   'integer',    ['default' => 0]);
        $this->table->addColumn('throttleduntil', 'datetime',   ['notnull' => false, 'default' => null]);
        $this->table->addColumn('roles',          'json_array', []);
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
