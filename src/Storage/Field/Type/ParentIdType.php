<?php

namespace Bolt\Storage\Field\Type;

use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Robert Hunt <robertgahunt@gmail.com>
 */
class ParentIdType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'parentid';
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('integer');
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageOptions()
    {
        return ['default' => 0];
    }
}
