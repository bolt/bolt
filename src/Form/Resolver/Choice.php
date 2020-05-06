<?php

namespace Bolt\Form\Resolver;

use ArrayObject;
use Bolt\Collection\Bag;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Query\QueryResultset;

/**
 * Choice resolver.
 *
 * @internal DO NOT USE. Will be replaced circa Bolt 3.5.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class Choice
{
    const DEFAULT_LIMIT = 500;
    
    /** @var Query */
    private $query;

    /**
     * Constructor.
     *
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @param ContentType $contentType
     * @param array       $templateFields
     *
     * @return array
     */
    public function get(ContentType $contentType, array $templateFields)
    {
        $select = new ArrayObject();

        $this->build($select, $contentType->getFields());
        $this->build($select, $templateFields, 'templatefields');

        return iterator_to_array($select) ?: null;
    }

    /**
     * Build the select array object.
     *
     * @param ArrayObject $select
     * @param array       $fields
     * @param bool        $isTemplateFields
     */
    private function build(ArrayObject $select, array $fields, $prefix = null)
    {
        foreach ($fields as $name => $field) {
            if ($field['type'] === 'repeater') {
                $subField = new ArrayObject();
                $this->build($subField, $field['fields']);
                if ($prefix) {
                    $select[$prefix][$name] = iterator_to_array($subField);
                } else {
                    $select[$name] = iterator_to_array($subField);
                }

            }
            if ($field['type'] === 'block') {
                foreach ($field['fields'] as $blockName => $block) {
                    $subField = new ArrayObject();
                    $this->build($subField, $block['fields']);
                    if ($prefix) {
                        $select[$prefix][$name][$blockName] = iterator_to_array($subField);
                    } else {
                        $select[$name][$blockName] = iterator_to_array($subField);
                    }
                }
            }
            $values = $this->getValues($field);
            if ($values !== null) {
                if ($prefix) {
                    $select[$prefix][$name] = $values;
                } else {
                    $select[$name] = $values;
                }
            }
        }
    }

    /**
     * @param array $field
     *
     * @return array|null
     */
    private function getValues(array $field)
    {
        if ($field['type'] !== 'select') {
            return null;
        }
        $field = Bag::from($field);

        // Get the appropriate values
        return is_string($field->get('values', [])) ? $this->getEntityValues($field) : $this->getYamlValues($field);
    }

    /**
     * Return a YAML defined array of select field value options.
     *
     * @param Bag $field
     *
     * @return array
     */
    private function getYamlValues(Bag $field)
    {
        $values = array_slice($field->get('values', []), 0, $field->get('limit', self::DEFAULT_LIMIT), true);
        if ($field->get('sortable')) {
            asort($values, SORT_REGULAR);
        }

        return $values;
    }

    /**
     * Return select field value options from a ContentType's records.
     *
     * @param Bag $field
     *
     * @return array
     */
    private function getEntityValues(Bag $field)
    {
        $baseParts = explode('/', $field->get('values'));
        if (count($baseParts) < 2) {
            throw new \InvalidArgumentException(sprintf('The "values" key for a ContentType select must be in the form of ContentType/field_name but "%s" given', $field->get('values')));
        }

        $contentType = $baseParts[0];
        $queryFields = explode(',', $baseParts[1]);
        foreach ($queryFields as $queryField) {
            if ($queryField) {
                continue;
            }
            throw new \InvalidArgumentException(sprintf('The "values" key for a ContentType select must include a single field, or comma separated fields, "%s" given', $field->get('values')));
        }

        $filter = $field->get('filter');
        $filter['order'] = $field->get('sort');
        $filter['limit'] = $field->get('limit', self::DEFAULT_LIMIT);
        /** @var QueryResultset $entities */
        $entities = $this->query->getContent($contentType, $filter);
        if (!$entities) {
            return [];
        }

        $values = [];
        $ctCount = count($entities->getOriginalQueries());
        foreach ($entities as $entity) {
            $id = $entity->get($field->get('keys', 'id'));

            if ($ctCount > 1) {
                $key = (string)$entity->getContenttype() . '/' . $id;
            } else {
                $key = $id;
            }

            $values[$key] = $entity->get($queryFields[0]);

            if (isset($queryFields[1])) {
                $values[$key] .= ' / ' . $entity->get($queryFields[1]);
            }
        }

        return $values;
    }
}
