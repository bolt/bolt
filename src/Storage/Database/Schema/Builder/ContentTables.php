<?php

namespace Bolt\Storage\Database\Schema\Builder;

use Bolt\Config;
use Bolt\Storage\Database\Schema\Table\ContentType;
use Bolt\Storage\Field\Manager as FieldManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

/**
 * Builder for Bolt content tables.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ContentTables extends BaseBuilder
{
    /** @var array */
    protected $tableSchemas;

    /**
     * Build the schema for Bolt ContentType tables.
     *
     * @param Schema $schema
     * @param Config $config
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getSchemaTables(Schema $schema, Config $config)
    {
        if ($this->tableSchemas !== null) {
            return $this->tableSchemas;
        }

        /** @var $fieldManager FieldManager */
        $fieldManager = $config->getFields();
        $contentTypes = $this->getNormalisedContentTypes($config);
        $tables = [];

        foreach ($this->tables->keys() as $name) {
            $contentType = $contentTypes[$name];
            /** @var ContentType $table */
            $table = $this->tables[$name];
            $tables[$name] = $table->buildTable($schema, $name, $this->charset, $this->collate);
            if (isset($contentType['fields']) && is_array($contentType['fields'])) {
                $this->addContentTypeTableColumns($this->tables[$name], $tables[$name], $contentType['fields'], $fieldManager);
            }
        }

        return $this->tableSchemas = $tables;
    }

    /**
     * Return an array of ContentTypes with the table name is the key.
     *
     * @param Config $config
     *
     * @return array
     */
    private function getNormalisedContentTypes(Config $config)
    {
        $normalised = [];
        $contentTypes = $config->get('contenttypes');
        foreach ($contentTypes as $contentType) {
            $normalised[$contentType['tablename']] = $contentType;
        }

        return $normalised;
    }

    /**
     * Add the custom columns for the ContentType.
     *
     * @param \Bolt\Storage\Database\Schema\Table\ContentType $tableObj
     * @param \Doctrine\DBAL\Schema\Table                     $table
     * @param array                                           $fields
     * @param FieldManager                                    $fieldManager
     */
    private function addContentTypeTableColumns(ContentType $tableObj, Table $table, array $fields, FieldManager $fieldManager)
    {
        // Check if all the fields are present in the DB.
        foreach ($fields as $fieldName => $values) {
            /** @var \Doctrine\DBAL\Platforms\Keywords\KeywordList $reservedList */
            $reservedList = $this->connection->getDatabasePlatform()->getReservedKeywordsList();
            if ($reservedList->isKeyword($fieldName)) {
                $error = sprintf(
                    "You're using '%s' as a field name, but that is a reserved word in %s. Please fix it, and refresh this page.",
                    $fieldName,
                    $this->connection->getDatabasePlatform()->getName()
                );
                $this->flashLogger->error($error);
                continue;
            }

            $this->addContentTypeTableColumn($tableObj, $table, $fieldName, $values, $fieldManager);
        }
    }

    /**
     * Add a single column to the ContentType table.
     *
     * @param \Bolt\Storage\Database\Schema\Table\ContentType $tableObj
     * @param \Doctrine\DBAL\Schema\Table                     $table
     * @param string                                          $fieldName
     * @param array                                           $values
     * @param FieldManager                                    $fieldManager
     */
    private function addContentTypeTableColumn(ContentType $tableObj, Table $table, $fieldName, array $values, FieldManager $fieldManager)
    {
        if ($tableObj->isKnownType($values['type'])) {
            // Use loose comparison on true as 'true' in YAML is a string
            $addIndex = isset($values['index']) && (boolean) $values['index'] === true;
            // Add the contenttype's specific fields
            $tableObj->addCustomFields($fieldName, $this->getContentTypeTableColumnType($values), $addIndex);
        } elseif ($handler = $fieldManager->getDatabaseField($values['type'])) {
            $type = ($handler->getStorageType() instanceof Type) ? $handler->getStorageType()->getName() : $handler->getStorageType();
            /** @var $handler \Bolt\Storage\Field\FieldInterface */
            $table->addColumn($fieldName, $type, $handler->getStorageOptions());
        }
    }

    /**
     * Certain field types can have single or JSON array types, figure it out.
     *
     * @param array $values
     *
     * @return string
     */
    private function getContentTypeTableColumnType(array $values)
    {
        // Multi-value selects are stored as JSON arrays
        if (isset($values['type']) && $values['type'] === 'select' && isset($values['multiple']) && $values['multiple'] === 'true') {
            return 'selectmultiple';
        }

        return $values['type'];
    }
}
