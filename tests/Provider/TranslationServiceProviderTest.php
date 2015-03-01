<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\TranslationServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/TranslationServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TranslationServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new TranslationServiceProvider($app);
        $app->register($provider);
        $app->boot();
        $this->assertNotNull($app['translator']->getLocale());
    }

    public function testLocaleChange()
    {
        $app = $this->getApp();
        $app['locale'] = 'de_XX';
        $provider = new TranslationServiceProvider($app);
        $app->register($provider);
        $app->boot();
        $this->assertEquals('de_XX', $app['translator']->getLocale());
    }
}
