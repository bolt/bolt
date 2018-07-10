<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\TextType;

/**
 * Comparison handling for MySQL/MariaDB platforms.
 *
 * @internal
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

        // MySQL does not support default value for TEXT/BLOB types
        foreach ($diff->changedColumns as $key => $columnDiff) {
            /* @var $columnDiff \Doctrine\DBAL\Schema\ColumnDiff */
            $column = $columnDiff->column;

            if ($columnDiff->hasChanged('default')
                && count($columnDiff->changedProperties) === 1
                && ($column->getType() instanceof TextType || $column->getType() instanceof BlobType)
            ) {
                unset($diff->changedColumns[$key]);
            }
        }
    }
}
