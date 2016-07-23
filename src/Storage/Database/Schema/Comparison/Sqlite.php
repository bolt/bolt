<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\TableDiff;

/**
 * Comparison handling for Sqlite platforms.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Sqlite extends BaseComparator
{
    /** @var string */
    protected $platform = 'sqlite';

    /**
     * {@inheritdoc}
     */
    protected function setIgnoredChanges()
    {
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'date', 'date');
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'datetime', 'datetime');
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'string', 'guid');
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'text', 'json_array');
    }

    /**
     * {@inheritdoc}
     */
    protected function removeIgnoredChanges(TableDiff $diff)
    {
        /**
         * @see https://github.com/doctrine/dbal/pull/242
         *      \Doctrine\DBAL\Platforms\SqlitePlatform::supportsForeignKeyConstraints
         *      https://www.sqlite.org/foreignkeys.html
         */
        if ($this->connection->getDatabasePlatform()->supportsForeignKeyConstraints() === false) {
            $diff->addedForeignKeys = [];
            $diff->changedForeignKeys = [];
            $diff->removedForeignKeys = [];
        }
    }
}
