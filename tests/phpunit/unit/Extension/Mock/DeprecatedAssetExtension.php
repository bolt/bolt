<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;
use Silex\Application;

/**
 * Mock extension that extends SimpleExtension for testing the AssetTrait deprecated functions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DeprecatedAssetExtension extends SimpleExtension
{
    private $registerFunction;
    private $registerParameters;

    public function setRegisterFunction($registerFunction, array $registerParameters)
    {
        $this->registerFunction = $registerFunction;
        $this->registerParameters = $registerParameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        call_user_func_array([$this, $this->registerFunction], $this->registerParameters);
    }

    public function snippetCallback($arg)
    {
        return sprintf('Drop Bear casualties today: %s', $arg);
    }
}
