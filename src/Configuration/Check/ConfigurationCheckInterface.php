<?php

namespace Bolt\Configuration\Check;

/**
 * System configuration check interface.
 *
 * @deprecated Since 3.4, to be removed in 4.0
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ConfigurationCheckInterface
{
    /**
     * Set the options for the check.
     *
     * @param array $options
     *
     * @return \Bolt\Configuration\Check\ConfigurationCheckInterface
     */
    public function setOptions(array $options);

    /**
     * Execute the test.
     *
     * @return \Bolt\Configuration\Check\Result
     */
    public function runCheck();
}
