<?php

namespace Bolt\Extension;

use Pimple as Container;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * This will replace current BaseExtension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class SimpleExtension extends AbstractExtension implements ServiceProviderInterface
{
    use AssetTrait;
    use MenuTrait;
    use TwigTrait;

    public function initialize(Container $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    final public function register(Application $app)
    {
        $this->registerTwigExtension();
        $this->registerMenuEntries();
        $this->registerAssets();

        $this->initialize($this->container);
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceProvider()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
