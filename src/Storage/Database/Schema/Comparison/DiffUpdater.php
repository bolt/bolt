<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * Processor for \Doctrine\DBAL\Schema\TableDiff objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DiffUpdater
{
    /** @var array */
    protected $ignoredChanges;
    /** @var array */
    protected $paramMap = [
        'addedColumns'       => 'checkColumn',
        'changedColumns'     => 'checkColumnDiff',
        'removedColumns'     => 'checkColumn',
        'renamedColumns'     => 'checkColumn',
        'addedIndexes'       => 'checkIndex',
        'changedIndexes'     => 'checkIndex',
        'removedIndexes'     => 'checkIndex',
        'renamedIndexes'     => 'checkIndex',
        'addedForeignKeys'   => 'checkForeignKeyConstraint',
        'changedForeignKeys' => 'checkForeignKeyConstraint',
        'removedForeignKeys' => 'checkForeignKeyConstraint',
    ];

    /**
     * Constructor.
     *
     * @param array $ignoredChanges
     */
    public function __construct(array $ignoredChanges)
    {
        $this->ignoredChanges = $ignoredChanges;
    }
}
