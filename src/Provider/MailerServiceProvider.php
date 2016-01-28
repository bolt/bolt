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
        
        $options = $app['config']->get('general/mailoptions');
        if ($options) {
            $app['swiftmailer.options'] = $options;
        }
        
        $spool = $app['config']->get('general/mailoptions/spool');
        if ($spool !== null) {
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
