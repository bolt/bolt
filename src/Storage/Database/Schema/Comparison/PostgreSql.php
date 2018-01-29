<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\TableDiff;

/**
 * Comparison handling for PostgreSQL platforms.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PostgreSql extends BaseComparator
{
    /** @var string */
    protected $platform = 'postgresql';

    /**
     * {@inheritdoc}
     */
    protected function setIgnoredChanges()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function removeIgnoredChanges(TableDiff $diff)
    {
    }
}
