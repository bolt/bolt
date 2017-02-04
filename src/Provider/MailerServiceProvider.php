<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider\SwiftmailerServiceProvider;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

/**
 * SwiftMailer integration.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MailerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        if (!isset($app['swiftmailer.options'])) {
            $app->register(new SwiftmailerServiceProvider());
        }

        $app['swiftmailer.options'] = function ($app) {
            return (array) $app['config']->get('general/mailoptions');
        };

        $app['swiftmailer.use_spool'] = function ($app) {
            return (bool) $app['config']->get('general/mailoptions/spool', true);
        };

        // Use the 'mail' transport. Discouraged, but some people want it. ¯\_(ツ)_/¯
        $transportFactory = $app->raw('swiftmailer.transport');
        $app['swiftmailer.transport'] = function ($app) use ($transportFactory) {
            if ($app['config']->get('general/mailoptions/transport') === 'mail') {
                return \Swift_MailTransport::newInstance();
            }

            return $transportFactory($app);
        };
    }
}
