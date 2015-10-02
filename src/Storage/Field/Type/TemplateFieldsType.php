<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Entity\TemplateFields;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\QuerySet;
use Bolt\TemplateChooser;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TemplateFieldsType extends FieldTypeBase
{
    public $mapping;
    public $em;
    public $chooser;

    public function __construct(array $mapping = [], EntityManager $em, TemplateChooser $chooser = null)
    {
        $this->mapping = $mapping;
        $this->chooser = $chooser;
        $this->em = $em;
        if ($em) {
            $this->setPlatform($em->createQueryBuilder()->getConnection()->getDatabasePlatform());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        $key = $this->mapping['fieldname'];
        $type = $this->getStorageType();
        $value = $type->convertToPHPValue($data[$key], $this->getPlatform());
        $this->set($entity, $value, $data);
    }

    public function set($entity, $value, $rawData = null)
    {
        $key = $this->mapping['fieldname'];
        $metadata = $this->buildMetadata($entity, $rawData);

        $builder = $this->em->getEntityBuilder('Bolt\Storage\Entity\TemplateFields');
        $builder->setClassMetadata($metadata);
        $templatefieldsEntity = $builder->createFromDatabaseValues($value);

        $ct = new ContentType('templatefields', ['fields' => $metadata->getFieldMappings()]);
        $templatefieldsEntity->setContenttype($ct);

        $entity->$key = $templatefieldsEntity;
    }

    public function persist(QuerySet $queries, $entity)
    {
        $key = $this->mapping['fieldname'];
        $qb = &$queries[0];
        $valueMethod = 'serialize'.ucfirst($key);
        $value = $entity->$valueMethod();

        $type = $this->getStorageType();

        if (null !== $value) {
            $metadata = $this->buildMetadata($entity);
            $value = $this->serialize($value, $metadata);
            $value = $type->convertToDatabaseValue($value, $this->getPlatform());
        } else {
            $value = isset($this->mapping['default']) ? $this->mapping['default'] : null;
        }

        $qb->setValue($key, ":".$key);
        $qb->set($key, ":".$key);
        $qb->setParameter($key, $value);
    }

    protected function serialize($input, $metadata)
    {
        $output = [];
        foreach ($metadata->getFieldMappings() as $field) {
            $fieldobj = $this->em->getFieldManager()->get($field['fieldtype'], $field);
            $type = $fieldobj->getStorageType();
            $key = $field['fieldname'];
            $output[$key] = $type->convertToDatabaseValue($input[$key], $this->getPlatform());
        }

        return $output;
    }

    protected function buildMetadata($entity, $rawData = null)
    {
        $template = $this->chooser->record($entity, $rawData);
        $metadata = new ClassMetadata(get_class($entity));

        if (isset($this->mapping['config'][$template])) {
            $mappings = $this->em->getMapper()->loadMetadataForFields($this->mapping['config'][$template]['fields']);
            $metadata->setFieldMappings((array)$mappings);
        }

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'templatefields';
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('json_array');
    }
}
