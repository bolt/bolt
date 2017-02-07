<?php

namespace Bolt\Configuration\Validation;

/**
 * Validation check interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ValidationInterface
{
    /**
     * Perform the validation check.
     */
    public function check();
}
