<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Storage\Entity\FieldValue;
use Doctrine\Common\Collections\ArrayCollection;
use Webmozart\Assert\Assert;

/**
 * A mapping of FieldValues.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class FieldCollection extends ArrayCollection implements FieldCollectionInterface
{
    /** @var int */
    protected $grouping;

    /**
     * Constructor.
     *
     * @param FieldValue[] $elements
     */
    public function __construct(array $elements = [])
    {
        parent::__construct([]);

        foreach ($elements as $value) {
            $this->add($value);
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function get($key)
    {
        $result = parent::get($key);

        if ($result instanceof FieldValue) {
            $result = $result->getValue();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setGrouping($grouping)
    {
        $this->grouping = $grouping;

        foreach ($this as $entity) {
            $entity->setGrouping($grouping);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($value)
    {
        Assert::isInstanceOf($value, FieldValue::class);

        $this->set($value->getFieldname(), $value);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        Assert::isInstanceOf($value, FieldValue::class);

        $value->setGrouping($this->grouping);

        parent::set($key, $value);
    }

    /**
     * @return \Iterator|FieldValue[]
     */
    public function getIterator()
    {
        return parent::getIterator();
    }
}
