<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for slug definition
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Slug extends Definition
{
    public function getUses()
    {
        $res = $this->get('uses', []);

        return (array) $res;
    }
}
