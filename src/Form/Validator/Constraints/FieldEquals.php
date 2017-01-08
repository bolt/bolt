<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Equivalent field value validation constraint.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FieldEquals extends Constraint
{
    public $message = 'This value does not equal the {{ field }} field value.';
    public $field;
    public $loose = false;
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
