<?php
namespace Bolt\Database;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Symfony\Component\HttpFoundation\Response;

/**
 * A response class for a single table's check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class IntegrityCheckerResponse
{
    /** @var string */
    private $title;
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
     * @param string $message
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
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
     * Check is there are pending messages.
     *
     * @return boolean
     */
    public function hasMessages()
    {
        return !empty($this->messages);
    }

    /**
     * Add title message.
     *
     * @param string $title
     */
    public function addTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get the message title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Check a Comparator diff and store the messages that make it up.
     *
     * @param string    $tableName
     * @param TableDiff $diff
     */
    public function checkDiff($tableName, TableDiff $diff)
    {
        $this->addTitle(sprintf('Table `%s` is not the correct schema:', $tableName));
        $this->getAddedColumns($diff);
        $this->getAddedIndexes($diff);
        $this->getChangedColumns($diff);
        $this->getChangedIndexes($diff);
        $this->getRemovedColumns($diff);
        $this->getRemovedIndexes($diff);

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
     * @param TableDiff $diff
     */
    private function getAddedColumns(TableDiff $diff)
    {
        /** @var $col \Doctrine\DBAL\Schema\Column */
        foreach ($diff->addedColumns as $col) {
            $this->addMessage(sprintf('missing column `%s`', $col->getName()));
        }
    }

    /**
     * Record added indexes.
     *
     * @param TableDiff $diff
     */
    private function getAddedIndexes(TableDiff $diff)
    {
        /** @var $index \Doctrine\DBAL\Schema\Index */
        foreach ($diff->addedIndexes as $index) {
            $this->addMessage(sprintf('missing index on `%s`', implode(', ', $index->getUnquotedColumns())));
        }
    }

    /**
     * Record changed columns.
     *
     * @param TableDiff $diff
     */
    private function getChangedColumns(TableDiff $diff)
    {
        /** @var $col \Doctrine\DBAL\Schema\ColumnDiff */
        foreach ($diff->changedColumns as $col) {
            $this->addMessage(sprintf('invalid column `%s`', $col->oldColumnName));
        }
    }

    /**
     * Record changed indexes.
     *
     * @param TableDiff $diff
     */
    private function getChangedIndexes(TableDiff $diff)
    {
        /** @var $index \Doctrine\DBAL\Schema\Index */
        foreach ($diff->changedIndexes as $index) {
            $this->addMessage(sprintf('invalid index on `%s`', implode(', ', $index->getUnquotedColumns())));
        }
    }

    /**
     * Record removed Columns.
     *
     * @param TableDiff $diff
     */
    private function getRemovedColumns(TableDiff $diff)
    {
        foreach (array_keys($diff->removedColumns) as $colName) {
            $this->addMessage(sprintf('removed column `%s`', $colName));
        }
    }

    /**
     * Record removed indexes.
     *
     * @param TableDiff $diff
     */
    private function getRemovedIndexes(TableDiff $diff)
    {
        foreach (array_keys($diff->removedIndexes) as $indexName) {
            $this->addMessage(sprintf('removed index `%s`', $indexName));
        }
    }
}
