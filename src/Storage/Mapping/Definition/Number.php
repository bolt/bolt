<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for Integer / Float field definitions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Number extends Definition
{
    public function getMin()
    {
        return $this->get('min', []);
    }

    public function getMax()
    {
        return $this->get('max', []);
    }

    public function getStep()
    {
        return $this->get('step', []);
    }
}
