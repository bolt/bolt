<?php

namespace Bolt\Provider;

use Bolt\Configuration\PreBoot;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Validation;
use Bolt\EventListener as Listener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Boot service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BootServiceProvider implements ServiceProviderInterface
{
    /**
     * Constructor.
     *
     * NOTE: Please do NOT under any circumstance assign $app as a class
     * variable, or do anything with the contain
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        if (!isset($app['boot.pre'])) {
            $this->preBoot($app['resources']);
            $app['boot.pre'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['boot.validator'] = $app->share(
            function ($app) {
                $verifier = new Validation\Validator($app['controller.exception'], $app['config'], $app['resources']);

                return $verifier;
            }
        );

        $app['boot.listener.checks'] = $app->share(
            function ($app) {
                return new Listener\BootInitListener($app);
            }
        );
    }

    /**
     * Internal pre-boot checks.
     *
     * @param ResourceManager $resourceManager
     */
    private function preBoot(ResourceManager $resourceManager)
    {
        PreBoot\ConfigurationFile::checkConfigFiles(
            ['config', 'contenttypes', 'menu', 'permissions', 'routing', 'taxonomy'],
            $resourceManager->getPath('src/../app/config'),
            $resourceManager->getPath('config')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['boot.listener.checks']);
    }
}
