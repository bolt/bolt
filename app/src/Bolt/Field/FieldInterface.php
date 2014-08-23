<?php

namespace Bolt\Field;

/**
 * Interface implemented by content fields.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
interface FieldInterface
{


    /**
     * Returns the name of the field.
     *
     * @return string The field name
     */
    public function getName();
    
    /**
     * Returns the path to the template.
     *
     * @return string The template name
     */
    public function getTemplate();

}
