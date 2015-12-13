<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
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
    /** @var \Bolt\Storage\Database\Schema\Comparison\IgnoredChange[] */
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
     * @param IgnoredChange[] $ignoredChanges
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
        /** @var IgnoredChange $ignoredChange */
        foreach ($this->ignoredChanges as $ignoredChange) {
            $alterName = $ignoredChange->getAlteration();

            // Array like 'changedColumns', 'changedIndexes', or 'changedForeignKeys'
            $schemaUpdateType = $tableDiff->$alterName;

            if (empty($schemaUpdateType)) {
                continue;
            }

            $this->checkChangedProperties($tableDiff, $schemaUpdateType, $alterName, $ignoredChange);
        }

        return $this->updateDiffTable($tableDiff);
    }

    /**
     * Check individual diff properties.
     *
     * @param TableDiff     $tableDiff
     * @param array         $schemaUpdateType
     * @param string        $alterName
     * @param IgnoredChange $ignoredChange
     */
    protected function checkChangedProperties(TableDiff $tableDiff, array $schemaUpdateType, $alterName, IgnoredChange $ignoredChange)
    {
        foreach ($schemaUpdateType as $columnName => $changeObject) {
            // Function name we need to call
            $func = $this->paramMap[$alterName];
            $needsUnset = call_user_func_array([$this, $func], [$changeObject, $ignoredChange]);
            if ($needsUnset) {
                unset($tableDiff->{$alterName}[$columnName]);
            }
        }
    }

    /**
     * Do checks for columns.
     *
     * @param Column        $column
     * @param IgnoredChange $ignoredChange
     *
     * @return boolean
     */
    protected function checkColumn(Column $column, IgnoredChange $ignoredChange)
    {
        // Not needed to be implemented yet
        if ($ignoredChange->getPropertyName() !== $column->getName()) {
            return false;
        }

        return false;
    }

    /**
     * Do checks for column diffs.
     *
     * @param ColumnDiff    $columnDiff
     * @param IgnoredChange $ignoredChange
     *
     * @return boolean
     */
    protected function checkColumnDiff(ColumnDiff $columnDiff, IgnoredChange $ignoredChange)
    {
        if (count($columnDiff->changedProperties) !== 1 && !$columnDiff->hasChanged($ignoredChange->getPropertyName())) {
            return false;
        }

        $propertyName = $columnDiff->changedProperties[0];
        $before = $columnDiff->fromColumn->getType()->getName();
        $after = $columnDiff->column->getType()->getName();
        if ($ignoredChange->matches('changedColumns', $propertyName, $before, $after)) {
            return true;
        }

        return false;
    }

    /**
     * Do checks for indexes.
     *
     * @param Index         $index
     * @param IgnoredChange $ignoredChange
     *
     * @return boolean
     */
    protected function checkIndex(Index $index, IgnoredChange $ignoredChange)
    {
        // Not needed to be implemented yet
        if ($ignoredChange->getPropertyName() !== $index->getName()) {
            return false;
        }

        return false;
    }

    /**
     * Do checks for foreignKey constraints.
     *
     * @param ForeignKeyConstraint $foreignKeyConstraint
     * @param IgnoredChange        $ignoredChange
     *
     * @return boolean
     */
    protected function checkForeignKeyConstraint(ForeignKeyConstraint $foreignKeyConstraint, IgnoredChange $ignoredChange)
    {
        // Not needed to be implemented yet
        if ($ignoredChange->getPropertyName() !== $foreignKeyConstraint->getName()) {
            return false;
        }

        return false;
    }

    /**
     * After post-processing a diff, check if we have anything left and respond
     * as Comparator::diffTable() would.
     *
     * @param TableDiff $diff
     *
     * @return TableDiff|false
     */
    private function updateDiffTable(TableDiff $diff)
    {
        foreach (array_keys($this->paramMap) as $param) {
            if (!empty($diff->{$param})) {
                return $diff;
            }
        }

        return false;
    }
}
