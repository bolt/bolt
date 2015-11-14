<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\MetadataDriver;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Relations Entities
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Relations extends ArrayCollection
{


    public function setFromPost($formValues, $entity)
    {
        if (isset($formValues['relation'])) {
            $flatVals = $formValues['relation'];
        } else {
            $flatVals = $formValues;
        }
        foreach ($flatVals as $field => $values) {
            foreach ($values as $val) {
                $taxentity = new Entity\Relations([
                    'from_contenttype' => $name,
                    'from_id' => $entity->getId(),
                    'to_contenttype' => (string)$entity->getContenttype(),
                    'to_id' => $field
                ]);
                $this->add($taxentity);
            }
        }
    }

    public function setFromDatabaseValues($result)
    {
        foreach ($result as $item) {
            $this->add(new Entity\Relations($item));
        }
    }

    /**
     * Runs a check on an incoming collection to make sure that duplicates are filtered out. Precedence is given to
     * records that are already persisted, with any diff in incoming properties updated.
     *
     * Any records not in the incoming set are deleted from the collection and the deleted ones returned as an array.
     *
     * @param Relations|Taxonomy $collection
     * @return array
     */
    public function update(Relations $collection)
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

    /**
     * Gets a specific taxonomy name from the overall collection
     *
     * @param $fieldname
     * @return Taxonomy
     */
    public function getField($fieldname)
    {
        return $this->filter(function ($el) use ($fieldname) {
            return $el->getTo_contenttype() == $fieldname;
        });
    }

    /**
     * Overrides the default to allow fetching a sub-selection.
     *
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->getField($offset);
    }


}