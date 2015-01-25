<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\TemplateChooserServiceProvider;

/**
 * Class to test src/Provider/TemplateChooserServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class TemplateChooserServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new TemplateChooserServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\TemplateChooser', $app['templatechooser']);
        $app->boot();
    }
 
   
}