<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;

/**
 * Interface for validation checks that require Config.
 *
 * @internal Do not use.
 *
 * @deprecated Deprecated since 3.1, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ConfigAwareInterface
{
    /**
     * @param Config $config
     */
    public function setConfig(Config $config);
}
