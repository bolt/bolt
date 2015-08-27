<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\EntityManager;
use Bolt\Storage\Hydrator;
use Bolt\Storage\Mapping\ClassMetadata;
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
    
    public $chooser;
    
    public function __construct(array $mapping = [], TemplateChooser $chooser = null)
    {
        $this->mapping = $mapping;
        $this->chooser = $chooser;
    }
    
    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        $key = $this->mapping['fieldname'];
        $type = $this->getStorageType();
        $value = $type->convertToPHPValue($data[$key], $em->createQueryBuilder()->getConnection()->getDatabasePlatform());
        
        if ($value) {
            $metadata = new ClassMetadata(get_class($entity));
            $currentTemplate = $this->chooser->record($data);
            if (isset($this->mapping['config'][$currentTemplate])) {
                $metadata->setFieldMappings($this->mapping['config'][$currentTemplate]['fields']);
            }
            $hydrator = new Hydrator($metadata);
            $entity->templatefields = $hydrator->hydrate($value);
        }
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
