<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\TableDiff;

/**
 * Comparison handling for MySQL/MariaDB platforms.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MySql extends BaseComparator
{
    /** @var string */
    protected $platform = 'mysql';

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
        // Work around reserved column name removal
        if ($diff->fromTable->getName() === $this->prefix . 'cron') {
            foreach ($diff->renamedColumns as $key => $col) {
                if ($col->getName() === 'interim') {
                    $diff->addedColumns[] = $col;
                    unset($diff->renamedColumns[$key]);
                }
            }
        }
    }
}
