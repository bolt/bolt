<?php
namespace Bolt\Storage\Database\Schema;

use Doctrine\DBAL\Schema\TableDiff;

/**
 * A response class for a single table's check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CheckResponse
{
    /** @var array */
    private $pending = [];
    /** @var array */
    private $titles = [];
    /** @var array */
    private $messages = [];
    /** @var array */
    private $hints = [];
    /** @var array */
    private $diffs = [];
    /** @var boolean */
    private $hinting;

    public function __construct($hinting = false)
    {
        $this->hinting = $hinting;
    }

    /**
     * Add a diff detail.
     *
     * @param string $detail
     */
    public function addDiffDetail($detail)
    {
        $this->diffs[] = $detail;
    }

    /**
     * Get the diff details.
     *
     * @return array
     */
    public function getDiffDetails()
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
            $response[] = $message;
        }

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
        $this->getAddedColumns($tableName, $diff);
        $this->getAddedIndexes($tableName, $diff);
        $this->getChangedColumns($tableName, $diff);
        $this->getChangedIndexes($tableName, $diff);
        $this->getRemovedColumns($tableName, $diff);
        $this->getRemovedIndexes($tableName, $diff);

        if ($this->hinting && count($diff->removedColumns) > 0) {
            $hint = sprintf(
                'The following fields in the `%s` table are not defined in your configuration. You can safely delete them manually if they are no longer needed: ',
                $tableName,
                join('`, `', array_keys($diff->removedColumns)));
            $this->addHint($hint);
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
     * Record removed Columns.
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
}
