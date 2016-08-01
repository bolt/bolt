<?php

namespace Bolt\Configuration\Validation;

use Bolt\Controller\ExceptionControllerInterface;

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
    public function check(ExceptionControllerInterface $exceptionController)
    {
        $safeMode = ini_get('safe_mode');
        if (is_string($safeMode)) {
            $safeMode = $safeMode == '1' || strtolower($safeMode) === 'on' ? 1 : 0;
        }

        if ($safeMode) {
            return $exceptionController->systemCheck(Validator::CHECK_SAFE_MODE);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isTerminal()
    {
        return true;
    }
}
