<?php
namespace Bolt\Field\Type;

use Bolt\Storage\EntityManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;


/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Geolocation extends FieldTypeBase
{
    
    /**
     * Returns the name of the field type.
     *
     * @return string The field name
     */
    public function getName()
    {
        return 'geolocation';
    }
    
    /**
     * Returns the name of the Doctrine storage type to use for a field.
     *
     * @return Type
     */
    public function getStorageType()
    {
        return Type::getType('json_array');
    }

    
}
