<?php

namespace Bolt\Storage\Database\Schema;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * A response class for a single table's check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SchemaCheck
{
    /** @var array */
    private $pending = [];
    /** @var array */
    private $titles = [];
    /** @var array */
    private $messages = [];
    /** @var array */
    private $hints = [];
    /** @var TableDiff[] */
    private $diffs = [];

    /**
     * Get a table diffs.
     *
     * @return TableDiff[]
     */
    public function getDiff()
    {
        return $this->diffs;
    }

    /**
     * Add a hint.
     *
     * @param string $hint
     */
    public function addHint($hint)
    {
        $this->hints[] = $hint;
    }

    /**
     * Get the hints.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * Check is there are pending hints.
     *
     * @return boolean
     */
    public function hasHints()
    {
        return !empty($this->hints);
    }

    /**
     * Add a message.
     *
     * @param string $tableName
     * @param string $message
     */
    public function addMessage($tableName, $message)
    {
        $this->pending[$tableName] = true;
        $this->messages[$tableName][] = $message;
    }

    /**
     * Get the messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get the response messages as a string.
     *
     * @return string
     */
    public function getResponseStrings()
    {
        $response = [];
        foreach (array_keys($this->pending) as $tableName) {
            $message = isset($this->titles[$tableName]) ? $this->titles[$tableName] . ' ' : '';
            if (!empty($this->messages[$tableName])) {
                $message .= implode(', ', $this->messages[$tableName]);
            }
            $response[$tableName] = $message;
        }
        ksort($response);

        return $response;
    }

    /**
     * Check is there are pending responses.
     *
     * @return boolean
     */
    public function hasResponses()
    {
        return !empty($this->pending);
    }

    /**
     * Add title message.
     *
     * @param string $tableName
     * @param string $title
     */
    public function addTitle($tableName, $title)
    {
        $this->pending[$tableName] = true;
        $this->titles[$tableName] = $title;
    }

    /**
     * Get the message titles.
     *
     * @return array
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * Check a Comparator diff and store the messages that make it up.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    public function checkDiff($tableName, TableDiff $diff)
    {
        $this->diffs[$tableName] = $diff;

        // Adds
        $this->getAddedColumns($tableName, $diff);
        $this->getAddedIndexes($tableName, $diff);
        $this->getAddedForeignKeys($tableName, $diff);

        // Changes
        $this->getChangedColumns($tableName, $diff);
        $this->getChangedIndexes($tableName, $diff);
        $this->getChangedForeignKeys($tableName, $diff);

        // Renames
        $this->getRenamedColumns($tableName, $diff);

        // Removes
        $this->getRemovedColumns($tableName, $diff);
        $this->getRemovedIndexes($tableName, $diff);
        $this->getRemovedForeignKeys($tableName, $diff);

        if (count($diff->removedColumns) > 0) {
            $hint = sprintf(
                'The following fields in the `%s` table are not defined in your configuration. You can safely delete them manually if they are no longer needed: `%s`',
                $tableName,
                join('`, `', array_keys($diff->removedColumns))
            );
            $this->hints[] = $hint;
        }
    }

    /**
     * Record added columns.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getAddedColumns($tableName, TableDiff $diff)
    {
        /** @var $col \Doctrine\DBAL\Schema\Column */
        foreach ($diff->addedColumns as $col) {
            $this->addMessage($tableName, sprintf('missing column `%s`', $col->getName()));
        }
    }

    /**
     * Record added indexes.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getAddedIndexes($tableName, TableDiff $diff)
    {
        /** @var $index \Doctrine\DBAL\Schema\Index */
        foreach ($diff->addedIndexes as $index) {
            $this->addMessage($tableName, sprintf('missing index on `%s`', implode(', ', $index->getUnquotedColumns())));
        }
    }

    /**
     * Record changed columns.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getChangedColumns($tableName, TableDiff $diff)
    {
        /** @var $col \Doctrine\DBAL\Schema\ColumnDiff */
        foreach ($diff->changedColumns as $col) {
            $this->addMessage($tableName, sprintf('invalid column `%s`', $col->oldColumnName));
        }
    }

    /**
     * Record changed indexes.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getChangedIndexes($tableName, TableDiff $diff)
    {
        /** @var $index \Doctrine\DBAL\Schema\Index */
        foreach ($diff->changedIndexes as $index) {
            $this->addMessage($tableName, sprintf('invalid index on `%s`', implode(', ', $index->getUnquotedColumns())));
        }
    }

    /**
     * Record removed columns.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getRemovedColumns($tableName, TableDiff $diff)
    {
        foreach (array_keys($diff->removedColumns) as $colName) {
            $this->addMessage($tableName, sprintf('removed column `%s`', $colName));
        }
    }

    /**
     * Record removed indexes.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getRemovedIndexes($tableName, TableDiff $diff)
    {
        foreach (array_keys($diff->removedIndexes) as $indexName) {
            $this->addMessage($tableName, sprintf('removed index `%s`', $indexName));
        }
    }

    /**
     * Record renamed columns.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getRenamedColumns($tableName, TableDiff $diff)
    {
        foreach (array_keys($diff->renamedColumns) as $colName) {
            $this->addMessage($tableName, sprintf('renamed column `%s`', $colName));
        }
    }

    /**
     * Record added foreign key(s).
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getAddedForeignKeys($tableName, TableDiff $diff)
    {
        foreach ($diff->addedForeignKeys as $foreignKey) {
            $this->addForeignKeysMessage($tableName, $foreignKey, 'missing foreign key on `%s` related to `%s.%s`');
        }
    }

    /**
     * Record changed foreign key(s).
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getChangedForeignKeys($tableName, TableDiff $diff)
    {
        foreach ($diff->changedForeignKeys as $foreignKey) {
            $this->addForeignKeysMessage($tableName, $foreignKey, 'changed foreign key on `%s`, it is now related to `%s.%s`');
        }
    }

    /**
     * Add a message for a foreign key change.
     *
     * @param string               $tableName
     * @param ForeignKeyConstraint $index
     * @param string               $format
     */
    private function addForeignKeysMessage($tableName, ForeignKeyConstraint $foreignKey, $format)
    {
        $this->addMessage(
            $tableName,
            sprintf(
                $format,
                implode(', ', $foreignKey->getUnquotedLocalColumns()),
                $foreignKey->getForeignTableName(),
                implode(', ' . $foreignKey->getForeignTableName() . '.', $foreignKey->getUnquotedForeignColumns())
            )
        );
    }

    /**
     * Record removed foreign key(s).
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    private function getRemovedForeignKeys($tableName, TableDiff $diff)
    {
        foreach ($diff->removedForeignKeys as $index) {
            $this->addMessage($tableName, sprintf('removed foreign key from `%s`', implode(', ', $index->getUnquotedLocalColumns())));
        }
    }
}
