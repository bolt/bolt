<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\MetadataDriver;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Taxonomy Entities
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Taxonomy extends ArrayCollection
{

    public $config;

    /**
     * Taxonomy constructor.
     * @param array $elements
     * @param MetadataDriver $metadata
     */
    public function __construct(array $elements = [], MetadataDriver $metadata = null)
    {
        parent::__construct($elements);
        if ($metadata) {
            $this->config = $metadata->getTaxonomyConfig();
        }
    }

    public function setFromPost($formValues, $entity)
    {
        foreach ($formValues['taxonomy'] as $field => $values) {
            foreach ($values as $val) {
                $order = isset($formValues['taxonomy-order'][$field]) ? $formValues['taxonomy-order'][$field] : 0;
                if (isset($this->config[$field]['options'][$val])) {
                    $name = $this->config[$field]['options'][$val];
                } else {
                    $name = $val;
                }
                $taxentity = new Entity\Taxonomy( [
                    'name' => $name,
                    'content_id' => $entity->getId(),
                    'contenttype' => (string)$entity->getContenttype(),
                    'taxonomytype' => $field,
                    'slug' => $val,
                    'sortorder' => $order
                ]);
                $this->add($taxentity);
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

    /*
     * @return array
     */
    public function getNew()
    {
        return $this->filter(function($el){
           return !$el->getId();
        });
    }

    public function getExisting()
    {
        return $this->filter(function($el){
            return $el->getId();
        });
    }

    /**
     * This loops over the existing collection to see if the properties in the incoming
     * are already available on a saved record. To do this it checks the three key properties
     * content_id, taxonomytype and slug, if there's a match it returns the original, otherwise
     * it returns the new and adds the new one to the collection.
     * @param $entity
     * @return mixed|null
     */
    public function getOriginal($entity)
    {
        foreach ($this as $k => $existing) {
            if (
                $existing->getContent_id() == $entity->getContent_id() &&
                $existing->getTaxonomytype() == $entity->getTaxonomytype() &&
                $existing->getSlug() == $entity->getSlug()
            ) {
                return $existing;
            }
        }

        return $entity;
    }

    public function removeElements(Taxonomy $incoming)
    {
        foreach ($this as $existing) {
            if (!$incoming->contains($existing)) {
                $this->removeElement($existing);
            }
        }
    }

    public function difference(Taxonomy $incoming)
    {
        $diff = new Taxonomy();
        foreach ($this as $existing) {
            if (!$incoming->contains($existing)) {
                $diff->add($existing);
            }
        }

        return $diff;
    }

    public function getField($fieldname)
    {
        return $this->filter(function($el) use($fieldname) {
            return $el->getTaxonomytype() == $fieldname;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function filter(Closure $p)
    {
        $elements = $this->toArray();
        $filtered = new static(array_filter($elements, $p));
        $filtered->config = $this->config;

        return $filtered;
    }

}