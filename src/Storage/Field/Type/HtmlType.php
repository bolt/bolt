<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Field\Sanitiser\SanitiserAwareInterface;
use Bolt\Storage\Field\Sanitiser\SanitiserAwareTrait;
use Bolt\Storage\Field\Sanitiser\WysiwygAwareInterface;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class HtmlType extends FieldTypeBase implements SanitiserAwareInterface, WysiwygAwareInterface
{
    use SanitiserAwareTrait;

    public function hydrate($data, $entity)
    {
        parent::hydrate($data, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'html';
    }

    /**
     * Returns the name of the Doctrine storage type to use for a field.
     *
     * @return Type
     */
    public function getStorageType()
    {
        return Type::getType('text');
    }
}
