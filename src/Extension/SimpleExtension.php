<?php

namespace Bolt\Extension;

use Bolt\Events\ControllerEvents;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generic base class for Bolt extensions.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class SimpleExtension extends AbstractExtension implements ServiceProviderInterface, EventSubscriberInterface, BootableProviderInterface
{
    use AssetTrait;
    use ConfigTrait;
    use ControllerTrait;
    use ControllerMountTrait;
    use MenuTrait;
    use NutTrait;
    use TwigTrait;
    use TranslationTrait;

    /**
     * {@inheritdoc}
     */
    final public function register(Container $app)
    {
        $this->extendConfigService();
        $this->extendTwigService();
        $this->extendMenuService();
        $this->extendAssetServices();
        $this->extendNutService();
        $this->extendTranslatorService();

        $this->registerServices($app);
    }

    /**
     * Register additional services for the extension.
     *
     * Example:
     * <pre>
     *   $app['koala'] = function ($app) {
     *       return new Koala($app['drop.bear']);
     *   };
     * </pre>
     *
     * @param Container $app
     */
    protected function registerServices(Container $app)
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
