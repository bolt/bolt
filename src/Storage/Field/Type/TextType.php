<?php

namespace Bolt\Storage\Field\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * Note: The persist() override was removed, because it was sanitising fields
 * when not desired. See https://github.com/bolt/bolt/issues/5789 for details.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TextType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'text';
    }
}
