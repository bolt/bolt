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
    /** @var array */
    protected $ignoredChanges = [
        'changedColumns' => [
            'propertyName'   => 'type',
            'ignoredChanges' => [
                ['before' => 'date', 'after' => 'date'],
                ['before' => 'datetime', 'after' => 'datetime'],
                ['before' => 'text', 'after' => 'json_array'],
            ],
        ]
    ];

    /**
     * {@inheritDoc}
     */
    protected function removeIgnoredChanges(TableDiff $diff)
    {
    }
}
