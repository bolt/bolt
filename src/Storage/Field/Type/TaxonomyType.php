<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\TaxonomyValue;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TaxonomyType extends FieldTypeBase
{
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
     * {@inheritdoc}
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];
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
            ->addSelect("$field.slug as " . $field . '_slug')
            ->addSelect($this->getPlatformGroupConcat("$field.name", $order, $field, $query))
            ->leftJoin($alias, 'bolt_taxonomy', $field, "$alias.id = $field.content_id AND $field.contenttype='$boltname' AND $field.taxonomytype='$field'")
            ->addGroupBy("$alias.id");
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        $taxValueProxy = [];
        $field = $this->mapping['fieldname'];
        $values = $entity->getTaxonomy();
        $taxData = $this->mapping['data'];
        $taxData['sortorder'] = isset($data[$field . '_sortorder']) ? $data[$field . '_sortorder'] : 0;
        $taxValues = array_filter(explode(',', $data[$field]));
        foreach ($taxValues as $taxValue) {
            $taxValueProxy[$field . '/' . $data[$field . '_slug']] = new TaxonomyValue($field, $taxValue, $taxData);

            if ($taxData['has_sortorder']) {
                // Previously we only cared about the last one… so yeah
                $index = array_search($data[$field . '_slug'], array_keys($taxData['options']));
                $sortorder = $taxData['sortorder'];
                $group = [
                    'slug'  => $data[$field . '_slug'],
                    'name'  => $taxValue,
                    'order' => $sortorder,
                    'index' => $index ?: 2147483647, // Maximum for a 32-bit integer
                ];
            }
        }

        $values[$field] = !empty($taxValueProxy) ? $taxValueProxy : null;
        $entity->setTaxonomy($values);
        $entity->setGroup($group);
        $entity->setSortorder($sortorder);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity, EntityManager $em = null)
    {
        $field = $this->mapping['fieldname'];
        $target = $this->mapping['target'];
        $taxonomy = $entity->getTaxonomy();

        // Fetch existing relations
        $existingQuery = $em->createQueryBuilder()
                            ->select('*')
                            ->from($target)
                            ->where('content_id = ?')
                            ->andWhere('contenttype = ?')
                            ->andWhere('taxonomytype = ?')
                            ->setParameter(0, $entity->id)
                            ->setParameter(1, $entity->getContenttype())
                            ->setParameter(2, $field);
        $result = $existingQuery->execute()->fetchAll();

        $existing = array_map(
            function ($el) {
                return $el ? $el['slug'] : [];
            },
            $result ?: []
        );
        $proposed = $taxonomy[$field] ?: [];

        $toInsert = array_diff($proposed, $existing);
        $toDelete = array_diff($existing, $proposed);

        foreach ($toInsert as $item) {
            $item = (string) $item;
            $ins = $em->createQueryBuilder()->insert($target);
            $ins->values([
                'content_id'   => '?',
                'contenttype'  => '?',
                'taxonomytype' => '?',
                'slug'         => '?',
                'name'         => '?',
            ])->setParameters([
                0 => $entity->id,
                1 => $entity->getContenttype(),
                2 => $field,
                3 => Slugify::create()->slugify($item),
                4 => isset($this->mapping['data']['options'][$item]) ? $this->mapping['data']['options'][$item] : $item,
            ]);

            $queries->append($ins);
        }

        foreach ($toDelete as $item) {
            $del = $em->createQueryBuilder()->delete($target);
            $del->where('content_id=?')
                ->andWhere('contenttype=?')
                ->andWhere('taxonomytype=?')
                ->andWhere('slug=?')
                ->setParameters([
                0 => $entity->id,
                1 => $entity->getContenttype(),
                2 => $field,
                3 => $item,
            ]);

            $queries->append($del);
        }
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
