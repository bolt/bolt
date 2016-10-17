<?php
namespace Bolt\Storage\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;

/**
 * Class FieldCollection holds a collection of Mapping\Definition objects
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class FieldCollection extends ArrayCollection
{

    /**
     * @param int|string $key
     * @param mixed $field
     * @return bool
     */
    public function set($key, $field)
    {
        if (!$field instanceof Definition) {
            throw new InvalidArgumentException("You can only add objects of the type ".Definition::class);
        }

        return parent::set($key, $field);
    }
}
