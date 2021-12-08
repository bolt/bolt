<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Common\Str;
use Bolt\Storage\Entity\Content;
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
     * @return string
     */
    public function getBlock()
    {
        return $this->first()->getBlock();
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($fieldName)
    {
        $this->initialize();

        return $this->collection->getFieldType($fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderedValue($fieldName)
    {
        $this->initialize();

        return $this->collection->getRenderedValue($fieldName);
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
            if ($instances === false) {
                return;
            }

            /** @var FieldValue $val */
            foreach ($instances as $val) {
                $fieldType = $val->getFieldType();
                $field = $this->em->getFieldManager()->getFieldFor($fieldType);
                $type = $field->getStorageType();
                $typeName = $type->getName();
                $typeCol = 'value_' . $typeName;
                $valCol = 'value_' . $typeName;
                if ($typeName === 'json') {
                    /** @deprecated since 3.3 to be renamed in v4. */
                    $valCol = 'value_json_array';
                }

                // Because there's a potential for custom fields that use json storage to 'double hydrate' this causes
                // json_decode to throw a warning. Here we prevent that by replacing the error handler.
                set_error_handler(
                    function ($errNo, $errStr, $errFile) {},
                    E_WARNING
                );
                $hydratedVal = $this->em->getEntityBuilder($val->getContenttype())->getHydratedValue($val->$typeCol, $val->getName(), $val->getFieldName());
                restore_error_handler();

                // If we do not have a hydrated value returned then we fall back to the one passed in
                if ($hydratedVal) {
                    $val->setValue($hydratedVal);
                } else {
                    $val->setValue($val->$valCol);
                }

                $this->collection->add($val);
            }
        }

        $this->em = null;
    }

    public function serialize($entity, $fieldArray, $query)
    {
        $output = [];
        $this->initialize();
        foreach ($this->collection as $field) {
            if ($field->getFieldType() == "select" && !is_array($fieldArray['data']['fields'][$field->getFieldName()]['values'])) {
                $referenceContent = $this->getReferencedContent($entity, $fieldArray['data']['fields'][$field->getFieldName()], $query, $field->getValue());
                $output[$field->getFieldName()] = $referenceContent;
            } else {
                $output[$field->getFieldName()] = $field->getValue();
            }
//            $output[$field->getFieldName()] = $field->getValue();
        }

        return $output;
    }

    private function getReferencedContent(Content $entity, array $field, $query, $searchId)
    {
        if (empty($searchId)) {
            return "";
        }

        $values = str_replace(["[", "]", '"'], '', $searchId);
        $values = explode(',', $values);
        $contentTypeName = Str::splitFirst($field['values'], '/');

        if (is_array($values)) {
            $val = [];
            foreach ($values as $value) {
                $reference = $contentTypeName . '/' . $value;
                $val[] = $this->getContent($reference, $contentTypeName, (string) $value, $query);
            }
        }

        return $val;
    }

    private function getContent(string $reference, string $contentTypeName, string $value, $query)
    {
        $referencedContent = $query->getContent($contentTypeName, ['id' => $value]);

        $val = [];

        /** @var Content $r */
        foreach ($referencedContent as $r) {
            $val = [
                'value' => (string) $value,
                '_id' => sprintf('%s/%s', $r->getContenttype(), $r->getSlug())
            ];
        }

        return $val;
    }
}
