<?php

namespace Bolt\Storage\Database\Schema\Builder;

use Bolt\Storage\Database\Schema\Table\BaseTable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Builder for Bolt extension tables.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionTables extends BaseBuilder
{
    /** @var callable[] */
    protected $tableGenerators = [];

    /** @var string @deprecated Deprecated since 3.0, to be removed in 4.0. */
    private $prefix;

    /**
     * Get all the registered extension tables.
     *
     * We need to be prepared for generators returning a single table, as well
     * as generators returning an array of tables.
     *
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getSchemaTables(Schema $schema)
    {
        $tables = [];

        foreach ($this->tables->keys() as $name) {
            /** @var BaseTable $table */
            $table = $this->tables[$name];
            $tables[$name] = $table->buildTable($schema, $name, $this->charset, $this->collate);
        }

        foreach ($this->tableGenerators as $generator) {
            $table = call_user_func($generator, $schema);

            if (is_array($table)) {
                /** @var Table $t */
                foreach ($table as $t) {
                    $alias = str_replace($this->prefix, '', $t->getName());
                    $t->addOption('alias', $alias);
                    $t->addOption('charset', $this->charset);
                    $t->addOption('collate', $this->collate);
                    $tables[$alias] = $t;
                }
            } else {
                /** @var Table $table */
                $alias = str_replace($this->prefix, '', $table->getName());
                $table->addOption('alias', $alias);
                $table->addOption('charset', $this->charset);
                $table->addOption('collate', $this->collate);
                $tables[$alias] = $table;
            }
        }

        return $tables;
    }

    /**
     * This method allows extensions to register their own tables.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param callable $generator A generator function that takes the Schema
     *                            instance and returns a table or an array of
     *                            tables.
     */
    public function addTable(callable $generator)
    {
        $this->tableGenerators[] = $generator;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $prefix
     */
    public function addPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
