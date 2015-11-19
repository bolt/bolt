<?php

namespace Bolt\Storage\Database\Schema\Builder;

use Doctrine\DBAL\Schema\Schema;

/**
 * Builder for Bolt core tables.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BaseTables extends BaseBuilder
{
    /**
     * Build the schema for base Bolt tables.
     *
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getSchemaTables(Schema $schema)
    {
        $tables = [];
        foreach ($this->tables->keys() as $name) {
            $tables[$name] = $this->tables[$name]->buildTable($schema, $this->prefix. $name, $name, $this->charset, $this->collate);
        }

        return $tables;
    }
}
