<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Field containing a value subset validator.
 *
 * Validates that a non-NULL value does not match part of the value of another
 * field.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FieldContainsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value) {
            return false;
        }

        $root = $this->context->getRoot();
        if (!$root->has($constraint->field)) {
            return false;
        }
        $otherValue = $root->get($constraint->field)->getData();

        if ($constraint->insensitive) {
            $value = strtolower($value);
            $otherValue = strtolower($otherValue);
        }

        if (strrpos($otherValue, $value) !== false) {
            return $this->addViolation($value, $constraint);
        }

        return false;
    }

    /**
     * Add violation.
     *
     * @param mixed      $value
     * @param Constraint $constraint
     *
     * @return true
     */
    protected function addViolation($value, Constraint $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('%field%', null)
            ->setParameter('%value%', $value)
            ->addViolation()
        ;

        return true;
    }
}
