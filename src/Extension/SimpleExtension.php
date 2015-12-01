<?php

namespace Bolt\Extension;

use Silex\Application;

/**
 * This will replace current BaseExtension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class SimpleExtension extends AbstractExtension
{
    use AssetTrait;
    use MenuTrait;
    use TwigTrait;

    abstract public function initialize(Application $app);

    /**
     * {@inheritdoc}
     */
    final public function register(Application $app)
    {
        $this->registerTwigExtension($app);

        $this->initialize($app);
    }
}
