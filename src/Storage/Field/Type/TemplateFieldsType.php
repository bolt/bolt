<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Legacy\ContentType;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Hydrator;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\MetadataDriver;
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
    public $metadata;
    
    public function __construct(array $mapping = [], TemplateChooser $chooser = null, MetadataDriver $metadata = null)
    {
        $this->mapping = $mapping;
        $this->chooser = $chooser;
        $this->metadata = $metadata;
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
            $currentTemplate = $this->chooser->record($entity);
            
            if (isset($this->mapping['config'][$currentTemplate])) {
                $mappings = $this->metadata->loadMetadataForFields($this->mapping['config'][$currentTemplate]['fields']);
                $metadata->setFieldMappings($mappings);
            }
            $hydrator = new Hydrator($metadata);
            $templatefieldsEntity = $hydrator->create();
            
            $ct = new ContentType('templatefields', ['fields'=>$mappings]);
            $templatefieldsEntity->setContenttype($ct);
            
            $hydrator->hydrate($templatefieldsEntity, $value, $em);
            $entity->templatefields = $templatefieldsEntity;
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
