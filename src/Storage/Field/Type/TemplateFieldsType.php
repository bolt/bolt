<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Common\Json;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\QuerySet;
use Bolt\TemplateChooser;
use Doctrine\DBAL\Types\Type;
use Twig\Environment;
use Twig\Error\LoaderError;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TemplateFieldsType extends FieldTypeBase
{
    /** @var TemplateChooser */
    public $chooser;
    /** @var Environment */
    private $twig;

    /**
     * Constructor.
     *
     * @param array           $mapping
     * @param EntityManager   $em
     * @param TemplateChooser $chooser
     * @param Environment     $twig
     */
    public function __construct(array $mapping, EntityManager $em, TemplateChooser $chooser, Environment $twig)
    {
        parent::__construct($mapping, $em);
        $this->chooser = $chooser;
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        /** @var string $key */
        $key = $this->mapping['fieldname'];
        $type = $this->getStorageType();
        $value = $type->convertToPHPValue($data[$key], $this->getPlatform());
        $this->set($entity, $value, $data);
    }

    /**
     * @param object $entity
     * @param mixed  $value
     * @param mixed  $rawData
     */
    public function set($entity, $value, $rawData = null)
    {
        $key = $this->mapping['fieldname'];
        $metadata = $this->buildMetadata($entity, $rawData);

        $builder = $this->em->getEntityBuilder(Entity\TemplateFields::class);
        $builder->setClassMetadata($metadata);
        $templatefieldsEntity = $builder->createFromDatabaseValues($value);

        $ct = new ContentType('templatefields', ['fields' => $metadata->getFieldMappings()]);
        $templatefieldsEntity->setContenttype($ct);

        $entity->$key = $templatefieldsEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $key = $this->mapping['fieldname'];
        $qb = &$queries[0];
        $valueMethod = 'serialize' . ucfirst($key);
        $value = $entity->$valueMethod();

        $type = $this->getStorageType();

        if ($value !== null) {
            $metadata = $this->buildMetadata($entity);
            $value = $this->serialize($value, $metadata);
            $value = $type->convertToDatabaseValue($value, $this->getPlatform());
        } else {
            $value = isset($this->mapping['default']) ? $this->mapping['default'] : null;
        }

        $qb->setValue($key, ':' . $key);
        $qb->set($key, ':' . $key);
        $qb->setParameter($key, $value);
    }

    /**
     * @param string        $input
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function serialize($input, ClassMetadata $metadata)
    {
        $output = [];
        foreach ($metadata->getFieldMappings() as $field) {
            $fieldObj = $this->em->getFieldManager()->get($field['fieldtype'], $field);
            $type = $fieldObj->getStorageType();
            $key = $field['fieldname'];

            // Hack … remove soon
            if (!isset($input[$key])) {
                continue;
            }
            if (Json::test($input[$key])) {
                $input[$key] = Json::parse($input[$key]);
            }
            $output[$key] = $type->convertToDatabaseValue($input[$key], $this->getPlatform());
        }

        return $output;
    }

    /**
     * @param object     $entity
     * @param array|null $rawData
     *
     * @return ClassMetadata
     */
    protected function buildMetadata($entity, $rawData = null)
    {
        $metadata = new ClassMetadata(get_class($entity));

        try {
            $template = $this->chooser->record($entity, $rawData);
            $template = $this->twig->resolveTemplate($template)->getSourceContext()->getName();
        } catch (LoaderError $e) {
            $template = null;
        }

        if ($template && isset($this->mapping['config'][$template])) {
            $mappings = $this->em->getMapper()->loadMetadataForFields($this->mapping['config'][$template]['fields']);
            $metadata->setFieldMappings((array) $mappings);
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
        return Type::getType('json');
    }
}
