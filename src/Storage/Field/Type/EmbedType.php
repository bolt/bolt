<?php

namespace Bolt\Storage\Field\Type;

use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class EmbedType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'embed';
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('json');
    }
}
