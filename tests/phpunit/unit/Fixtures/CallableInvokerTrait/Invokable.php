<?php

namespace Bolt\Tests\Fixtures\CallableInvokerTrait;

/**
 * Test fixture for CallableInvokerTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Invokable
{
    public function __invoke($koala = null, $dropbear = null)
    {
        return $this->findMe($koala, $dropbear);
    }

    private function findMe($koala = null, $dropbear = null)
    {
        return 'found-' . $koala . '-' . $dropbear;
    }
}
