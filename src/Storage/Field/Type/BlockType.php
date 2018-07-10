<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Field\Collection\RepeatingFieldCollection;

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

    /**
     * Normalize step ensures that we have correctly hydrated objects at the collection
     * and entity level.
     *
     * @param $entity
     */
    public function normalize($entity)
    {
        $key = $this->mapping['fieldname'];
        $accessor = 'get' . ucfirst($key);

        $outerCollection = $entity->$accessor();
        if (!$outerCollection instanceof RepeatingFieldCollection) {
            $collection = new RepeatingFieldCollection($this->em, $this->mapping);
            $collection->setName($key);

            if (is_array($outerCollection)) {
                foreach ($outerCollection as $group => $block) {
                    foreach ($block as $blockName => $fields) {
                        if (is_array($fields)) {
                            unset($fields['__internal']);
                            $collection->addFromArray($fields, $group, $entity, $blockName);
                        }
                    }
                }
            }

            $setter = 'set' . ucfirst($key);
            $entity->$setter($collection);
        }
    }
}
