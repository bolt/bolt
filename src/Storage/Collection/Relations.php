<?php

namespace Bolt\Storage\Collection;

use Bolt\Exception\StorageException;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\EntityProxy;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Relations Entities
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Relations extends ArrayCollection
{
    protected $em;

    /**
     * Relations constructor.
     *
     * @param array         $elements
     * @param EntityManager $em
     */
    public function __construct(array $elements = [], EntityManager $em = null)
    {
        parent::__construct($elements);
        $this->em = $em;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    public function setFromPost($formValues, $entity)
    {
        if (isset($formValues['relation'])) {
            $flatVals = $formValues['relation'];
        } else {
            $flatVals = $formValues;
        }
        foreach ($flatVals as $field => $values) {
            foreach ($values as $val) {
                if (!$val) {
                    continue;
                }
                $newentity = new Entity\Relations([
                    'from_contenttype' => (string)$entity->getContenttype(),
                    'from_id'          => $entity->getId(),
                    'to_contenttype'   => $field,
                    'to_id'            => $val
                ]);
                $this->add($newentity);
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
     * @param Relations $collection
     *
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
     * are already available on a saved record. To do this it checks the four key properties
     * if there's a match it returns the original, otherwise
     * it returns the new and adds the new one to the collection.
     *
     * @param $entity
     *
     * @return mixed|null
     */
    public function getOriginal($entity)
    {
        foreach ($this as $k => $existing) {
            if (
                $existing->getFrom_id() == $entity->getFrom_id() &&
                $existing->getFrom_contenttype() == $entity->getFrom_contenttype() &&
                $existing->getTo_contenttype() == $entity->getTo_contenttype() &&
                $existing->getTo_id() == $entity->getTo_id()
            ) {
                return $existing;
            }
        }

        return $entity;
    }

    /**
     * Gets a specific relation type name from the overall collection
     *
     * @param $fieldname
     *
     * @return Relations
     */
    public function getField($fieldname)
    {
        return $this->filter(function ($el) use ($fieldname) {
            return $el->getTo_contenttype() == $fieldname;
        });
    }

    /**
     * Identifies which relations are incoming to the given entity
     *
     * @param $entity
     *
     * @return mixed
     */
    public function incoming($entity)
    {
        return $this->filter(function ($el) use ($entity) {
            return $el->getTo_contenttype() == (string)$entity->getContenttype();
        });
    }

    /**
     * Overrides the default to allow fetching a sub-selection.
     *
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        if ($this->em === null) {
            throw new StorageException('Unable to load collection values. Ensure that EntityManager is set on '.__CLASS__);
        }
        $collection = new LazyCollection();
        $proxies = $this->getField($offset);
        foreach ($proxies as $proxy) {
            $collection->add(new EntityProxy($proxy->to_contenttype, $proxy->to_id, $this->em));
        }

        return $collection;
    }
}
