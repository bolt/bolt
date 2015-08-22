<?php
namespace Bolt\Storage\Field\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class IntegerType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integer';
    }
}
