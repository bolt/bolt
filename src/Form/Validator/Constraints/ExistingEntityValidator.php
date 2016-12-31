<?php

namespace Bolt\Form\Validator\Constraints;

use Bolt\Storage\Entity\Entity;
use Bolt\Storage\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Existing entity validator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExistingEntityValidator extends ConstraintValidator
{
    /** @var EntityManager */
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->hasEntity($value, $constraint)) {
            $this->addViolation($value, $constraint);
        }
    }

    /**
     * Check if the validating entity has a record with a matching field value.
     *
     * @param mixed      $value
     * @param Constraint $constraint
     *
     * @return bool
     */
    protected function hasEntity($value, Constraint $constraint)
    {
        $repo = $this->em->getRepository($constraint->className);
        /** @var Entity $formEntity */
        $formEntity = $this->context->getRoot()->getData();
        $query = $repo->createQueryBuilder()
            ->setParameter('value', $value)
        ;
        foreach ((array) $constraint->fieldNames as $fieldName) {
            $query->orWhere($fieldName . ' = :value');
        }

        $entity = $repo->findOneWith($query);
        if ($entity === false) {
            return false;
        }
        if ($entity->getId() === $formEntity->getId()) {
            return false;
        }

        return true;
    }

    /**
     * Add violation.
     *
     * @param mixed      $value
     * @param Constraint $constraint
     */
    protected function addViolation($value, Constraint $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('%type%', $constraint->className)
            ->setParameter('%fields%', implode(', ', (array) $constraint->fieldNames))
            ->setParameter('%value%', $value)
            ->addViolation()
        ;
    }
}
