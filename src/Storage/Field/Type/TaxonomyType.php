<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\TaxonomyValue;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TaxonomyType extends FieldTypeBase
{
    use TaxonomyTypeTrait;

    /**
     * Taxonomy fields allows queries on the parameters passed in.
     * For example the following queries:
     *     'pages', {'categories'=>'news'}
     *     'pages', {'categories'=>'news || events'}.
     *
     * Because the search is actually on the join table, we replace the
     * expression to filter the join side rather than on the main side.
     *
     * @param QueryInterface $query
     * @param ClassMetadata  $metadata
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];

        foreach ($query->getFilters() as $filter) {
            if ($filter->getKey() == $field) {

                // This gets the method name, one of andX() / orX() depending on type of expression
                $method = strtolower($filter->getExpressionObject()->getType()).'X';

                $newExpr = $query->getQueryBuilder()->expr()->$method();
                foreach ($filter->getParameters() as $k => $v) {
                    $newExpr->add("$field.slug = :$k");
                }

                $filter->setExpression($newExpr);
            }
        }
    }

    /**
     * For the taxonomy field the load event modifies the query to fetch taxonomies related
     * to a content record from the join table.
     *
     * It does this via an additional ->addSelect() and ->leftJoin() call on the QueryBuilder
     * which includes then includes the taxonomies in the same query as the content fetch.
     *
     * @param QueryBuilder  $query
     * @param ClassMetadata $metadata
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];
        $target = $this->mapping['target'];
        $boltname = $metadata->getBoltName();

        if ($this->mapping['data']['has_sortorder']) {
            $order = "$field.sortorder";
            $query->addSelect("$field.sortorder as " . $field . '_sortorder');
        } else {
            $order = "$field.id";
        }

        $from = $query->getQueryPart('from');

        if (isset($from[0]['alias'])) {
            $alias = $from[0]['alias'];
        } else {
            $alias = $from[0]['table'];
        }

        $query
            ->addSelect($this->getPlatformGroupConcat("$field.slug", $order, $field.'_slugs', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.name", $order, $field, $query))
            ->leftJoin($alias, $target, $field, "$alias.id = $field.content_id AND $field.contenttype='$boltname' AND $field.taxonomytype='$field'")
            ->addGroupBy("$alias.id");
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        $group = null;
        $sortorder = null;
        $taxValueProxy = [];
        $values = $entity->getTaxonomy();
        $taxName = $this->mapping['fieldname'];
        $taxData = $this->mapping['data'];
        $taxData['sortorder'] = isset($data[$taxName . '_sortorder']) ? $data[$taxName . '_sortorder'] : 0;
        $taxValues = $this->getTaxonomyValues($taxName, $data);

        foreach ($taxValues as $taxValueSlug => $taxValueName) {
            if (empty($taxValueSlug)) {
                continue;
            }
            
            $keyName = $taxName . '/' . $taxValueSlug;
            $taxValueProxy[$keyName] = new TaxonomyValue($taxName, $taxValueName, $taxData);

            if ($taxData['has_sortorder']) {
                // Previously we only cared about the last one… so yeah
                $needle = isset($data[$taxName . '_slug']) ? $data[$taxName . '_slug'] : $data[$taxName];
                $index = array_search($needle, array_keys($taxData['options']));
                $sortorder = $taxData['sortorder'];
                $group = [
                    'slug'  => $taxValueSlug,
                    'name'  => $taxValueName,
                    'order' => $sortorder,
                    'index' => $index ?: 2147483647, // Maximum for a 32-bit integer
                ];
            }
        }

        $values[$taxName] = !empty($taxValueProxy) ? $taxValueProxy : null;

        foreach ($values as $tname => $tval) {
            $setter = 'set'.ucfirst($tname);
            $entity->$setter($tval);
        }
        
        $entity->setTaxonomy($values);
        $entity->setGroup($group);
        $entity->setSortorder($sortorder);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $field = $this->mapping['fieldname'];
        $taxonomy = $entity->getTaxonomy();
        $taxonomy[$field] = isset($taxonomy[$field]) ? $this->filterArray($taxonomy[$field]) : [];

        // Fetch existing taxonomies
        $result = $this->getExistingTaxonomies($entity) ?: [];
        if ($this->mapping['data']['behaves_like'] === 'tags') {
            // We transform to [key => value] as 'tags' entry doesn't contain a slug
            $existing = array_map(
                function (&$k, $v) {
                    if ($v) {
                        $k = $v['slug'];
                        $v = $v['name'];
                    }

                    return $v;
                },
                array_keys($result),
                $result
            );
        } else {
            $existing = array_map(
                function ($v) {
                    return $v ? $v['slug'] : [];
                },
                $result
            );
        }

        $toInsert = array_diff($taxonomy[$field], $existing);
        $toDelete = array_diff($existing, $taxonomy[$field]);

        $this->appendInsertQueries($queries, $entity, $toInsert);
        $this->appendDeleteQueries($queries, $entity, $toDelete);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'taxonomy';
    }

    /**
     * Get platform specific group_concat token for provided column.
     *
     * @param string       $column
     * @param string       $order
     * @param string       $alias
     * @param QueryBuilder $query
     *
     * @return string
     */
    protected function getPlatformGroupConcat($column, $order, $alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();

        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(DISTINCT $column ORDER BY $order ASC) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT(DISTINCT $column) as $alias";
            case 'postgresql':
                return "string_agg(distinct $column, ',' ORDER BY $order) as $alias";
        }
    }
}
