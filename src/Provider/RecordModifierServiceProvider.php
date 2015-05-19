<?php

namespace Bolt\Provider;

use Bolt\Storage\RecordModifier;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Temprary DI SP until Ross Riley merges the storage branch to avoid conflicts.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordModifierServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['storage.record_modifier'] = $app->share(
            function ($app) {
                $cm = new RecordModifier($app);

                return $cm;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
