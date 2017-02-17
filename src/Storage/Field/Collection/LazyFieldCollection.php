<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Storage\Entity\FieldValue;
use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\AbstractLazyCollection;

/**
 * This class is used by lazily loaded field values. It stores a reference to an array of rows and
 * fetches from the database on demand.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LazyFieldCollection extends AbstractLazyCollection implements FieldCollectionInterface
{
    /** @var int[] */
    protected $references = [];
    /** @var EntityManager|null */
    protected $em;
    /** @var int */
    protected $grouping;
    /** @var FieldCollectionInterface */
    protected $collection;

    /**
     * Constructor.
     *
     * @param int[]              $references
     * @param EntityManager|null $em
     */
    public function __construct(array $references = [], EntityManager $em = null)
    {
        $this->references = $references;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getNew()
    {
        $this->initialize();
        return $this->collection->getNew();
    }

    /**
     * {@inheritdoc}
     */
    public function getExisting()
    {
        $this->initialize();
        return $this->collection->getExisting();
    }

    /**
     * {@inheritdoc}
     */
    public function setGrouping($grouping)
    {
        if (!$this->initialized) {
            $this->grouping = $grouping;
        } else {
            $this->collection->setGrouping($grouping);
        }
    }

    /**
     * Handles the conversion of references to entities.
     */
    protected function doInitialize()
    {
        $this->collection = new FieldCollection();
        $this->collection->setGrouping($this->grouping);

        if ($this->references) {
            $repo = $this->em->getRepository(FieldValue::class);
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
                $hydratedVal = $this->em->getEntityBuilder($val->getContenttype())->getHydratedValue($val->$typeCol, $val->getName(), $val->getFieldname());
                restore_error_handler();

                // If we do not have a hydrated value returned then we fall back to the one passed in
                if ($hydratedVal) {
                    $val->setValue($hydratedVal);
                } else {
                    $val->setValue($val->$typeCol);
                }

                $this->collection->add($val);
            }
        }

        $this->em = null;
    }
}
