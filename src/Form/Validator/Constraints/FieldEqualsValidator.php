<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Equivalent field value validator.
 *
 * Validates that a non-NULL value does not match the value of another field.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FieldEqualsValidator extends ConstraintValidator
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

        if ($value === $otherValue) {
            return $this->addViolation($value, $constraint);
        }

        if ($constraint->loose && $value == $otherValue) {
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
