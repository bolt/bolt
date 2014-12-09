<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\TranslationServiceProvider;

/**
 * Class to test src/Provider/TranslationServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
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
 
   
}