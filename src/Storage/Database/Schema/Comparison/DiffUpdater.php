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

    /**
     * Platform specific adjustments to table/column diffs.
     *
     * @param TableDiff $tableDiff
     *
     * @return TableDiff|false
     */
    public function adjustDiff(TableDiff $tableDiff)
    {
        foreach ($this->ignoredChanges as $alterKey => $alterData) {
            // Array from something like 'changedColumns', 'changedIndexes',
            // or 'changedForeignKeys'
            $schemaUpdateType = $tableDiff->$alterKey;

            if (empty($schemaUpdateType)) {
                continue;
            }
            $this->checkChangedProperties($tableDiff, $schemaUpdateType, $alterKey, $alterData);
        }

        return $this->updateDiffTable($tableDiff);
    }

    /**
     * Check individual diff properties.
     *
     * @param TableDiff $tableDiff
     * @param array     $schemaUpdateType
     * @param string    $alterKey
     * @param array     $alterData
     */
    protected function checkChangedProperties(TableDiff $tableDiff, array $schemaUpdateType, $alterKey, array $alterData)
    {
        foreach ($schemaUpdateType as $columnName => $changeObject) {
            // Function name we need to call
            $func = $this->paramMap[$alterKey];
            $needsUnset = call_user_func_array([$this, $func], [$changeObject, $alterData]);
            if ($needsUnset) {
                unset($tableDiff->{$alterKey}[$columnName]);
            }
        }
    }

    /**
     * Do checks for columns.
     *
     * @param Column $column
     * @param array  $alterData
     *
     * @return boolean
     */
    protected function checkColumn(Column $column, array $alterData)
    {
        // Not needed to be implemented yet
        if ($alterData['propertyName'] !== $column->getName()) {
            return false;
        }

        return false;
    }

    /**
     * Do checks for column diffs.
     *
     * @param ColumnDiff $columnDiff
     * @param array      $alterData
     *
     * @return boolean
     */
    protected function checkColumnDiff(ColumnDiff $columnDiff, array $alterData)
    {
        if (count($columnDiff->changedProperties) !== 1 && !$columnDiff->hasChanged($alterData['propertyName'])) {
            return false;
        }

        foreach ($alterData['ignoredChanges'] as $keys) {
            if ($keys['before'] === $columnDiff->fromColumn->getType()->getName() &&
                $keys['after'] === $columnDiff->column->getType()->getName()) {
                return true;
            }
        }
        return false;
    }
}
