<?php

namespace Bolt\Storage\Collection;

use Bolt\Common\Deprecated;
use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\MetadataDriver;
use Closure;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Taxonomy Entities.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Taxonomy extends ArrayCollection
{
    public $config;

    /**
     * Taxonomy constructor.
     *
     * @param array          $elements
     * @param MetadataDriver $metadata
     */
    public function __construct(array $elements = [], MetadataDriver $metadata = null)
    {
        parent::__construct($elements);
        if ($metadata) {
            $this->config = $metadata->getTaxonomyConfig();
        }
    }

    /**
     * @param array          $formValues
     * @param Entity\Content $entity
     */
    public function setFromPost(array $formValues, Entity\Content $entity)
    {
        if (!isset($formValues['taxonomy'])) {
            return;
        }
        $flatVals = $formValues['taxonomy'];
        foreach ($flatVals as $field => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $val) {
                $order = isset($formValues['taxonomy-order'][$field]) ? $formValues['taxonomy-order'][$field] : 0;
                if (is_array($val) && isset($val['slug'])) {
                    $val = $val['slug'];
                }
                if (isset($this->config[$field]['options'][$val])) {
                    $name = $this->config[$field]['options'][$val];
                } else {
                    $name = $val;
                }
                $taxEntity = new Entity\Taxonomy([
                    'name'         => $name,
                    'content_id'   => $entity->getId(),
                    'contenttype'  => (string) $entity->getContenttype(),
                    'taxonomytype' => $field,
                    'slug'         => Slugify::create()->slugify($val),
                    'sortorder'    => $order,
                ]);
                $this->add($taxEntity);
            }
        }
    }

    public function setFromDatabaseValues($result)
    {
        foreach ($result as $item) {
            $this->add(new Entity\Taxonomy($item));
        }
    }

    /**
     * Runs a check on an incoming collection to make sure that duplicates are filtered out. Precedence is given to
     * records that are already persisted, with any diff in incoming properties updated.
     *
     * Any records not in the incoming set are deleted from the collection and the deleted ones returned as an array.
     *
     * @param Taxonomy $collection
     *
     * @return array
     */
    public function update(Taxonomy $collection)
    {
        $updated = [];
        // First give priority to already existing entities
        foreach ($collection as $entity) {
            $entity->setSlug(str_replace('/', '', $entity->getSlug()));
            $master = $this->getOriginal($entity);
            $master->setSortorder($entity->getSortorder());
            $updated[] = $master;
        }

        $deleted = [];
        foreach ($this as $old) {
            if (!in_array($old, $updated)) {
                $deleted[] = $old;
            }
        }

        // Clear the collection so that we re-add only the updated elements
        $this->clear();
        foreach ($updated as $new) {
            $this->add($new);
        }

        return $deleted;
    }

    /**
     * Get the taxonomy types that are in the collection, grouped by taxonomy key.
     *
     * @internal
     *
     * @return array
     */
    public function getGrouped()
    {
        $types = [];
        $elements = $this->toArray();
        /** @var Entity\Taxonomy $element */
        foreach ($elements as $element) {
            $type = $element->get('taxonomytype');
            $types[$type][] = $element;
        }

        return $types;
    }

    /*
     * Gets the elements that have not yet been persisted.
     *
     * @return Taxonomy
     */
    public function getNew()
    {
        return $this->filter(function ($el) {
            /** @var Entity\Taxonomy $el */
            return !$el->getId();
        });
    }

    /**
     * Gets the elements that have already been persisted.
     *
     * @return Taxonomy
     */
    public function getExisting()
    {
        return $this->filter(function ($el) {
            /** @var Entity\Taxonomy $el */
            return $el->getId();
        });
    }

    /**
     * This loops over the existing collection to see if the properties in the incoming
     * are already available on a saved record. To do this it checks the three key properties
     * content_id, taxonomytype and slug, if there's a match it returns the original, otherwise
     * it returns the new and adds the new one to the collection.
     *
     * @param Entity\Taxonomy $entity
     *
     * @return mixed|null
     */
    public function getOriginal($entity)
    {
        /** @var Entity\Taxonomy $existing */
        foreach ($this as $k => $existing) {
            if (
                $existing->getContentId() === $entity->getContentId() &&
                $existing->getTaxonomytype() === $entity->getTaxonomytype() &&
                $existing->getSlug() === $entity->getSlug()
            ) {
                return $existing;
            }
        }

        return $entity;
    }

    /**
     * Gets a specific taxonomy name from the overall collection.
     *
     * @param string $fieldName
     *
     * @return Taxonomy
     */
    public function getField($fieldName)
    {
        return $this->filter(function ($el) use ($fieldName) {
            /** @var Entity\Taxonomy $el */
            return $el->getTaxonomytype() === $fieldName;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $p)
    {
        $elements = $this->toArray();
        $filtered = new static(array_filter($elements, $p));
        $filtered->config = $this->config;

        return $filtered;
    }

    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->getField($offset);
    }

    public function containsKeyValue($field, $value)
    {
        foreach ($this->getField($field) as $element) {
            if ($element->getSlug() === $value) {
                return true;
            }
        }

        return false;
    }

    public function getSortorder($field, $slug)
    {
        foreach ($this->getField($field) as $element) {
            if ($element->getSlug() === $slug) {
                return $element->getSortorder();
            }
        }
    }

    public function serialize()
    {
        $output = [];
        foreach ($this as $k => $existing) {
            $output[] = ['slug' => $existing->getSlug(), 'name' => $existing->getName()];
        }

        return $output;
    }

    /**
     * @return null|string
     */
    public function getGroupingTaxonomy()
    {
        Deprecated::method(3.4, 'getGroupingTaxonomies');

        foreach ($this->config->getData() as $taxKey => $taxonomy) {
            if ($taxonomy['behaves_like'] === 'grouping') {
                return $taxKey;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getGroupingTaxonomies()
    {
        $result = [];

        foreach ($this->config->getData() as $taxKey => $taxonomy) {
            if ($taxonomy['behaves_like'] === 'grouping') {
                $result[] = $taxKey;
            }
        }

        return $result;
    }
}
