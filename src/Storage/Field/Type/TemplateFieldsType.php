<?php
namespace Bolt\Storage\Field\Type;

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
            $repo = $em->getRepository($entity->getContenttype());
            $templateEntity = $repo->create($value);
            $entity->templatefields = $templateEntity;
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
