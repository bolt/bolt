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
     * {@inheritDoc}
     */
    protected function setIgnoredChanges()
    {
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'date', 'date');
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'datetime', 'datetime');
        $this->ignoredChanges[] = new IgnoredChange('changedColumns', 'type', 'text', 'json_array');
    }

    /**
     * {@inheritDoc}
     */
    protected function removeIgnoredChanges(TableDiff $diff)
    {
    }
}
