<?php
namespace Bolt\Field\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Html extends FieldTypeBase
{
    /**
     * Returns the name of the field type.
     *
     * @return string The field name
     */
    public function getName()
    {
        return 'html';
    }
}
