<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * YAML validation constraint.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Yaml extends Constraint
{
    public $message = 'This provided data does not parse as valid YAML: %error%.';
}
