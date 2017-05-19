<?php

namespace Bolt\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\SwiftmailerServiceProvider;

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
    }
}
