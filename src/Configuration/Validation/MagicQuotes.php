<?php

namespace Bolt\Configuration\Validation;

use Bolt\Controller\ExceptionControllerInterface;

/**
 * Magic quotes validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MagicQuotes implements ValidationInterface
{
    /**
     * {@inheritdoc}
     */
    public function check(ExceptionControllerInterface $exceptionController)
    {
        if (get_magic_quotes_gpc()) {
            return $exceptionController->systemCheck(Validator::CHECK_MAGIC_QUOTES);
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
