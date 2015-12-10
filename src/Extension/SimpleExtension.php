<?php

namespace Bolt\Extension;

use Bolt\Events\ControllerEvents;
use Pimple as Container;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This will replace current BaseExtension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class SimpleExtension extends AbstractExtension implements ServiceProviderInterface, EventSubscriberInterface
{
    use AssetTrait;
    use ConfigTrait;
    use MenuTrait;
    use TwigTrait;
    use ControllerMountTrait;
    use NutTrait;

    public function initialize(Container $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    final public function register(Application $app)
    {
        $this->extendTwigService();
        $this->extendMenuService();
        $this->extendAssetServices();
        $this->extendNutService();

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
        $this->container['dispatcher']->addSubscriber($this);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ControllerEvents::MOUNT => 'onMount',
        ];
    }
}
