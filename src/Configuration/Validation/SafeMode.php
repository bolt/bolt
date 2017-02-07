<?php

namespace Bolt\Configuration\Validation;

use Bolt\Exception\Configuration\Validation\System\SafeModeValidationException;

/**
 * Safe mode validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SafeMode implements ValidationInterface
{
    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $safeMode = ini_get('safe_mode');
        if (is_string($safeMode)) {
            $safeMode = $safeMode == '1' || strtolower($safeMode) === 'on';
        }

        if ($safeMode) {
            throw new SafeModeValidationException();
        }
    }
}
