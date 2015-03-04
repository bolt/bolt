<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\TemplateChooserServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/TemplateChooserServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
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
