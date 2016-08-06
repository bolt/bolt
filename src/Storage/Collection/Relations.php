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

    /**
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array          $formValues
     * @param Entity\Content $entity
     */
    public function setFromPost(array $formValues, Entity\Content $entity)
    {
        if (isset($formValues['relation'])) {
            $flatVals = $formValues['relation'];
        } else {
            $flatVals = $formValues;
        }
        foreach ($flatVals as $field => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $val) {
                if (!$val) {
                    continue;
                }
                $newEntity = new Entity\Relations([
                    'from_contenttype' => (string) $entity->getContenttype(),
                    'from_id'          => $entity->getId(),
                    'to_contenttype'   => $field,
                    'to_id'            => $val,
                ]);
                $this->add($newEntity);
            }
        }
    }

    /**
     * @param array $result
     */
    public function setFromDatabaseValues(array $result)
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
     * @param Entity\Relations $entity
     *
     * @return mixed|null
     */
    public function getOriginal(Entity\Relations $entity)
    {
        foreach ($this as $k => $existing) {
            if (
                $existing->getFromId() == $entity->getFromId() &&
                $existing->getFromContenttype() === $entity->getFromContenttype() &&
                $existing->getToContenttype() === $entity->getToContenttype() &&
                $existing->getToId() == $entity->getToId()
            ) {
                return $existing;
            }
        }

        return $entity;
    }

    /**
     * Gets a specific relation type name from the overall collection
     *
     * @param string $fieldName
     * @param bool   $biDirectional
     * @param string $contentTypeName
     * @param int    $contentTypeId
     *
     * @return Relations
     */
    public function getField($fieldName, $biDirectional = false, $contentTypeName = null, $contentTypeId = null)
    {
        if ($biDirectional) {
            $filter = function ($el) use ($fieldName, $contentTypeName, $contentTypeId) {
                /** @var Entity\Relations $el */
                if ($el->getFromContenttype() === $fieldName && $el->getFromContenttype() === $el->getToContenttype() && $el->getToId() == $contentTypeId) {
                    $el->actAsInverse();

                    return true;
                }
                if ($el->getToContenttype() === $fieldName && $el->getFromContenttype() === $contentTypeName) {
                    return true;
                }
                if ($el->getFromContenttype() === $fieldName && $el->getToContenttype() === $contentTypeName) {
                    $el->actAsInverse();

                    return true;
                }

                return false;
            };

            return $this->filter($filter);
        }

        return $this->filter(function ($el) use ($fieldName) {
            /** @var Entity\Relations $el */
            return $el->getToContenttype() === $fieldName;
        });
    }

    /**
     * Identifies which relations are incoming to the given entity
     *
     * @param Entity\Content $entity
     *
     * @return mixed
     */
    public function incoming(Entity\Content $entity)
    {
        return $this->filter(function ($el) use ($entity) {
            /** @var Entity\Relations $el */
            return $el->getToContenttype() == (string) $entity->getContenttype() && $el->getToId() === $entity->getId();
        });
    }

    /**
     * Overrides the default to allow fetching a sub-selection.
     *
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->em === null) {
            throw new StorageException('Unable to load collection values. Ensure that EntityManager is set on ' . __CLASS__);
        }
        $collection = new LazyCollection();
        $proxies = $this->getField($offset);
        foreach ($proxies as $proxy) {
            $collection->add(new EntityProxy($proxy->to_contenttype, $proxy->to_id, $this->em));
        }

        return $collection;
    }
}
