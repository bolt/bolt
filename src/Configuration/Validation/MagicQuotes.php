<?php

namespace Bolt\Configuration\Validation;

use Bolt\Exception\Configuration\Validation\System\MagicQuotesValidationException;

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
    public function check()
    {
        if (get_magic_quotes_gpc()) {
            throw new MagicQuotesValidationException();
        }
    }
}
