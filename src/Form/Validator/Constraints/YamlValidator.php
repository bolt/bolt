<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as Parser;

/**
 * YAML validator.
 *
 * Validates a given input string parses as valid YAML.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class YamlValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_string($value)) {
            return $this->addViolation('YAML input was not a string.', $constraint);
        }

        try {
            $arr = Parser::parse($value, true);
        } catch (ParseException $e) {
            return $this->addViolation($e->getMessage(), $constraint);
        }

        if (is_string($arr)) {
            return $this->addViolation("YAML parses as a string. Did you forget a colon ':' at the end of the first key?", $constraint);
        }

        return false;
    }

    /**
     * Add violation.
     *
     * @param mixed           $error
     * @param Constraint|Yaml $constraint
     *
     * @return true
     */
    protected function addViolation($error, Constraint $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('%error%', $error)
            ->addViolation()
        ;

        return true;
    }
}
