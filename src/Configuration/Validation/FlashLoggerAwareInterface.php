<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Logger\FlashLoggerInterface;

/**
 * Interface for validation checks that require FlashLoggerInterface.
 *
 * @internal Do not use.
 *
 * @deprecated Deprecated since 3.2, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface FlashLoggerAwareInterface
{
    /**
     * @param FlashLoggerInterface $flashLogger
     */
    public function setFlashLogger(FlashLoggerInterface $flashLogger);
}
