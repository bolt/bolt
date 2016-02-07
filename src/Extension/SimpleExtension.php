<?php

namespace Bolt\Extension;

use Bolt\Events\ControllerEvents;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
    use ControllerTrait;
    use ControllerMountTrait;
    use MenuTrait;
    use NutTrait;
    use TwigTrait;

    /**
     * {@inheritdoc}
     */
    final public function register(Application $app)
    {
        $this->extendConfigService();
        $this->extendTwigService();
        $this->extendMenuService();
        $this->extendAssetServices();
        $this->extendNutService();

        $this->registerServices($app);
    }

    /**
     * Register additional services for the extension.
     *
     * Example:
     * <pre>
     *   $app['koala'] = $app->share(
     *       function ($app) {
     *           return new Koala($app['drop.bear']);
     *       }
     *   );
     * </pre>
     *
     * @param Application $app
     */
    protected function registerServices(Application $app)
    {
    }

    /**
     * Define events to listen to here.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [$this];
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $this->container = $app;
        $this->container['dispatcher']->addSubscriber($this);
        $this->subscribe($this->container['dispatcher']);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ControllerEvents::MOUNT => [
                ['onMountRoutes', 0],
                ['onMountControllers', 0],
            ],
        ];
    }
}
