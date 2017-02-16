<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 *  This class is used by lazily loaded field values. It stores a reference to an array of rows and
 *  fetches from the database on demand.
 *
 *  @author Ross Riley <riley.ross@gmail.com>
 */
class FieldCollection extends AbstractLazyCollection
{
    public $references = [];
    protected $em;
    protected $grouping;
    protected $block;
    protected $toRemove = [];

    /**
     * @param array              $references
     * @param EntityManager|null $em
     */
    public function __construct(array $references = [], EntityManager $em = null)
    {
        $this->references = $references;
        $this->em = $em;
    }

    /**
     * @return array
     */
    public function getNew()
    {
        $created = [];
        foreach ($this as $k => $entity) {
            $id = $entity->getId();
            if (!$id) {
                $created[] = $entity;
            }
        }

        return $created;
    }

    /**
     * @return array
     */
    public function getExisting()
    {
        $set = [];
        foreach ($this as $k => $entity) {
            $id = $entity->getId();
            if ($id) {
                $set[] = $entity;
            }
        }

        return $set;
    }

    /**
     * @param mixed $grouping
     */
    public function setGrouping($grouping)
    {
        $this->grouping = $grouping;
    }

    /**
     * @param mixed $block
     */
    public function setBlock($block)
    {
        $this->block = $block;
    }

    /**
     * @return string
     */
    public function getBlock()
    {
        $this->initialize();
        return $this->first()->getBlock();
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    public function add($element)
    {
        $element->setGrouping($this->grouping);
        $element->setBlock($this->block);

        return parent::add($element);
    }

    /**
     * Helper method to get the value for a specific field
     * this is compatible with content.get(contentkey) calls from twig.
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $this->initialize();

        foreach ($this->collection as $field) {
            if ($field->getFieldname() == $key) {
                return $field->getValue();
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->initialize();

        foreach ($this->collection as $field) {
            if ($field->getFieldname() === $offset) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Handles the conversion of references to entities.
     */
    protected function doInitialize()
    {
        $objects = [];
        if ($this->references) {
            $repo = $this->em->getRepository('Bolt\Storage\Entity\FieldValue');
            $instances = $repo->findBy(['id' => $this->references]);

            foreach ((array) $instances as $val) {
                $fieldtype = $val->getFieldtype();
                $field = $this->em->getFieldManager()->getFieldFor($fieldtype);
                $type = $field->getStorageType();
                $typeCol = 'value_' . $type->getName();

                // Because there's a potential for custom fields that use json storage to 'double hydrate' this causes
                // json_decode to throw a warning. Here we prevent that by replacing the error handler.
                set_error_handler(
                    function ($errNo, $errStr, $errFile) {},
                    E_WARNING
                );
                $block = !empty($val->getBlock()) ? $val->getBlock() : null;
                $hydratedVal = $this->em->getEntityBuilder($val->getContenttype())->getHydratedValue($val->$typeCol, $val->getName(), $val->getFieldname(), $block);
                restore_error_handler();

                // If we do not have a hydrated value returned then we fall back to the one passed in
                if ($hydratedVal) {
                    $val->setValue($hydratedVal);
                } else {
                    $val->setValue($val->$typeCol);
                }

                $objects[$val->getFieldname()] = $val;
            }
        }

        $this->collection = new ArrayCollection($objects);
        $this->em = null;
    }
}
