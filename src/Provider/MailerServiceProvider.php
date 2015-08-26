<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\ServiceProviderInterface;

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
    public function register(Application $app)
    {
        if (!isset($app['swiftmailer.options'])) {
            $app->register(new SwiftmailerServiceProvider());
        }

        if ($options = $app['config']->get('general/mailoptions')) {
            $app['swiftmailer.options'] = $options;
        }

        if ($spool = $app['config']->get('general/mailoptions/spool') !== null) {
            $app['swiftmailer.use_spool'] = $spool;
        }

        if ($app['config']->get('general/mailoptions/transport') === 'mail') {
            // Use the 'mail' transport. Discouraged, but some people want it. ¯\_(ツ)_/¯
            $app['swiftmailer.transport'] = $app->share(
                function () {
                    return \Swift_MailTransport::newInstance();
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
