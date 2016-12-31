<?php
namespace Bolt\Storage\Field\Type;

/**
 * This class adds a block collection and handles additional functionality for adding
 * named blocks.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BlockType extends RepeaterType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'block';
    }
}
