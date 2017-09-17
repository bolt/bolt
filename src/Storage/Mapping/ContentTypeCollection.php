<?php
namespace Bolt\Storage\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;

/**
 * Class ContentTypeCollection holds a collection of Mapping\ContentType objects
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class ContentTypeCollection extends ArrayCollection
{

    /**
     * @param int|string $key
     * @param ContentType $contentType
     * @return bool
     */
    public function set($key, $contentType)
    {
        if (!$contentType instanceof ContentType) {
            throw new InvalidArgumentException("You can only add objects of the type ".ContentType::class);
        }

        return parent::set($key, $contentType);
    }
}
