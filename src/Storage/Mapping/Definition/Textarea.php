<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for textarea definitions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Textarea extends Definition
{
    public function getAllowtwig()
    {
        return $this->get('allowtwig', false);
    }
}