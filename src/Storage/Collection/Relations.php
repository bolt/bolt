<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\EntityProxy;
use Doctrine\Common\Collections\ArrayCollection;
use DomainException;

/**
 * This class stores an array collection of Relations Entities.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Relations extends ArrayCollection
{
    protected $em;

    protected $owner;

    /**
     * Relations constructor.
     *
     * @param array               $elements
     * @param EntityManager       $em
     * @param Entity\Content|null $owner
     */
    public function __construct(array $elements = [], EntityManager $em = null, Entity\Content $owner = null)
    {
        parent::__construct($elements);
        $this->em = $em;
        $this->owner = $owner;
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
    public function setFromPost(array $formValues, Entity\Content $entity = null)
    {
        if (!isset($formValues['relation'])) {
            return;
        }

        if ($entity === null && $this->getOwner() !== null) {
            $entity = $this->getOwner();
        }

        $flatVals = $formValues['relation'];
        foreach ($flatVals as $field => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $val) {
                if (!$val) {
                    continue;
                }
                $this->addEntity($field, $val, $entity);
            }
        }
    }

    /**
     * Adds a related entity by type, id and owner
     *
     * @param string         $type
     * @param int            $id
     * @param Entity\Content $owner
     */
    protected function addEntity($type, $id, Entity\Content $owner)
    {
        $newEntity = new Entity\Relations([
            'from_contenttype' => (string) $owner->getContenttype(),
            'from_id'          => $owner->getId(),
            'to_contenttype'   => $type,
            'to_id'            => $id,
        ]);
        $this->add($newEntity);
    }

    /**
     * Associate related items by type and identifiers
     *
     * @param string|Entity\Content $type
     * @param array|null            $items
     */
    public function associate($type, array $items = null)
    {
        if ($this->getOwner() === null) {
            throw new DomainException('Unable to associate relations to a collection that does not have an owning entity!');
        }
        if ($type instanceof Entity\Content) {
            $this->addEntity((string) $type->getContenttype(), $type->getId(), $this->getOwner());

            return;
        }

        if (is_iterable($type)) {
            foreach ($type as $entity) {
                if ($entity instanceof Entity\Content) {
                    $this->associate($entity);
                }
            }

            return;
        }

        foreach ($items as $item) {
            $this->addEntity($type, $item, $this->getOwner());
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
     * Get the related types that are in the collection, grouped by ContentType key.
     *
     * @internal
     *
     * @return array
     */
    public function getGrouped()
    {
        $types = [];
        $elements = $this->toArray();
        /** @var Entity\Relations $element */
        foreach ($elements as $element) {
            $type = $element->get('to_contenttype');
            $types[$type][] = $element;
        }

        return $types;
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
     * Gets a specific relation type name from the overall collection.
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
     * Identifies which relations are incoming to the given entity.
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
        return $this->buildLazyCollection($this->getField($offset));
    }

    /**
     * This method is compatible with the above offsetGet in that it returns a collection of lazy loaded content
     * entity objects, this time for all relations no matter what the contenttype
     *
     * @return LazyCollection
     */
    public function all()
    {
        return $this->buildLazyCollection($this->toArray());
    }

    /**
     * @param iterable $items
     *
     * @return LazyCollection
     */
    protected function buildLazyCollection($items)
    {
        if ($this->em === null) {
            return parent::getIterator();
        }
        $collection = new LazyCollection();

        foreach ($items as $proxy) {
            $collection->add(new EntityProxy($proxy->to_contenttype, $proxy->to_id, $this->em));
        }

        return $collection;
    }

    public function serialize()
    {
        $output = [];
        foreach ($this as $k => $existing) {
            $output[$existing->getToContenttype()][] = spl_object_hash($existing);
        }

        return $output;
    }

    /**
     * @return Entity\Content
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set a reference to the owning entity.
     *
     * @param Entity\Content $owner
     */
    public function setOwner(Entity\Content $owner)
    {
        $this->owner = $owner;
    }
}
