<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Helpers\Arr;
use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for select and multipleselect definitions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Select extends Definition
{
    public function getValues()
    {
        $res = $this->get('values', []);

        if (Arr::isIndexed($res)) {
            $res = array_combine($res, $res);
        }

        return $res;
    }
}