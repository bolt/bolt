<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\MetadataDriver;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Taxonomy Entities
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Taxonomy extends ArrayCollection
{

    protected $config;

    /**
     * Taxonomy constructor.
     * @param MetadataDriver $metadata
     */
    public function __construct(MetadataDriver $metadata)
    {
        $this->config = $metadata->getTaxonomyConfig();
    }

    public function setFromPost($formValues, $entity)
    {
        foreach ($formValues['taxonomy'] as $field => $values) {
            foreach ($values as $val) {
                $order = isset($formValues['taxonomy-order'][$field]) ? $formValues['taxonomy-order'][$field] : 0;
                if (isset($this->config[$field]['options'][$val])) {
                    $name = $this->config[$field]['options'][$val];
                } else {
                    $name = null;
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
     * Any records not in the incoming set are deleted from the collection.
     *
     * @param Taxonomy $collection
     */
    public function merge(Taxonomy $collection)
    {
        // First give priority to already existing entities
        foreach ($collection as $k => $entity) {
            $master = $this->getExisting($entity);
            $master->setSortorder() == $entity->getSortorder();
            if (!$this->contains($master)) {
                $this->add($master);
            }
        }

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

    public function getExisting($entity)
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

}