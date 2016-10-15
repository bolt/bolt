<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for Date / Datetime field definitions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Date extends Definition
{
    public function getOptions()
    {
        return $this->get('options', []);
    }
}