<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\TemplateChooserServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/TemplateChooserServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TemplateChooserServiceProviderTest extends BoltFunctionalTestCase
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
