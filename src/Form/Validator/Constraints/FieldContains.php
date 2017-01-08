<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Field containing a value subset validation constraint.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FieldContains extends Constraint
{
    public $message = 'This value does not contain the {{ field }} field value.';
    public $field;
    public $insensitive = false;

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'field';
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return ['field'];
    }
}
