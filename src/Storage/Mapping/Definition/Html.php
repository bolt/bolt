<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for HTML field definitions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Html extends Definition
{
    public function getOptions()
    {
        return $this->get('options', []);
    }
}
