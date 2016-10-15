<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for Date / Datetime field definitions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Templateselect extends Definition
{
    public function getFilter()
    {
        return $this->get('filter', false);
    }
}