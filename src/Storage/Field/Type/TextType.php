<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\EntityProxy;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TextType extends FieldTypeBase
{
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'text';
    }
}
