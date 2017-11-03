<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Collection;
use Bolt\Storage\Entity;
use Bolt\Storage\Query\Filter;
use Bolt\Storage\Query\QueryInterface;
use Doctrine\Common\Collections;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use ReflectionProperty;
use Traversable;

/**
 * This is an abstract class that field types dealing with join/association functionality can extend.
 * It provides standard helpers to perform complex loads/queries/hydration across join tables.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
abstract class JoinTypeBase extends FieldTypeBase
{
    /**
     * This method takes the flat underscore separated key->value data that comes from the query result
     * and turns it into a properly structured array. The second part of the method also removes duplicate
     * values in the case where an aggregate query returns more than one copy of the row.
     *
     * For example, `_from_id => "4,4,4"` gets normalized to `['fromid'=>4]`
     *
     * @param Traversable $data
     * @param string      $field
     * @param string      $separator
     *
     * @return array
     */
    protected function normalizeData($data, $field, $separator = ',')
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (strpos($key, '_') === 0 && strpos($key, $field) === 1) {
                $path = explode('_', str_replace('_' . $field, '', $key));
                $normalized[$path[1]] = $value;
            }
        }

        $compiled = [];

        foreach ($normalized as $key => $value) {
            if ($value === null) {
                continue;
            }
            foreach (explode($separator, $value) as $i => $val) {
                $compiled[$i][$key] = $val;
            }
        }
        $compiled = array_unique($compiled, SORT_REGULAR);

        return $compiled;
    }

    /**
     * This method does an in-place modification of a generic contenttype.field query to the format actually used
     * in the raw sql category. For instance a simple query might say `entries.tags = 'movies'` but now we are in the
     * context of entries the actual SQL fragment needs to be `tags.slug = 'movies'`. We don't know this until we
     * drill down to the individual field types so this rewrites the SQL fragment just before the query gets sent.
     *
     * Note, reflection is used to achieve this, it is not ideal, but the CompositeExpression shipped with DBAL chooses
     * to keep the query parts as private and only allow access to the final computed string.
     *
     * @param Filter         $filter
     * @param QueryInterface $query
     * @param string         $field
     * @param string         $column
     */
    protected function rewriteQueryFilterParameters(Filter $filter, QueryInterface $query, $field, $column)
    {
        $originalExpression = $filter->getExpressionObject();

        $reflected = new ReflectionProperty(CompositeExpression::class, 'parts');
        $reflected->setAccessible(true);

        $reflected2 = new ReflectionProperty(CompositeExpression::class, 'type');
        $reflected2->setAccessible(true);

        $originalParts = $reflected->getValue($originalExpression);
        foreach ($originalParts as &$part) {
            /** @var \Bolt\Storage\Query\SelectQuery $query */
            $part = str_replace('_' . $query->getContenttype() . '.' . $field, $field . '.' . $column, $part);
        }
        $reflected->setValue($originalExpression, $originalParts);

        if ($originalExpression->getType() === 'AND' && count($originalParts) > 1) {
            $platform = $query->getQueryBuilder()->getConnection()->getDatabasePlatform();
            $reflected2->setValue($originalExpression, 'OR');
            $reflected->setValue($originalExpression, [1]);
            foreach ($query->getWhereParametersFor($field) as $paramKey => $paramValue) {
                $query->getQueryBuilder()->andHaving($platform->getConcatExpression("','", '_' . $field . '_' . str_replace('_', '', $column), "','") . ' LIKE(' . ':_having_' . $paramKey . ')');
                $query->getQueryBuilder()->setParameter('_having_' . $paramKey, "%,$paramValue,%");
            }
        }

        $filter->setExpression($originalExpression);
    }

    /**
     * @param object|Entity\Content $entity
     * @param string                $target
     *
     * @return Collections\ArrayCollection|null
     */
    public function normalizeFromPost($entity, $target)
    {
        $key = $this->mapping['fieldname'];
        $accessor = 'get' . ucfirst($key);

        $outerCollection = $entity->$accessor();
        if ($outerCollection === null || $outerCollection instanceof Collections\Collection) {
            return null;
        }

        /** @var Collection\Taxonomy|Collection\Relations $collection */
        $collection = $this->em->createCollection($target);

        if (is_string($outerCollection)) {
            $outerCollection = [$outerCollection];
        }

        if (is_array($outerCollection)) {
            $related = [
                $key => $outerCollection,
            ];
            $collection->setFromPost($related, $entity);
        }

        return $collection;
    }
}
