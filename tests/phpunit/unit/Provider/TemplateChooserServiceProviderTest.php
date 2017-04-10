<?php

namespace Bolt\Tests\Provider;

use Bolt\TemplateChooser;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\TemplateChooserServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TemplateChooserServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(TemplateChooser::class, $app['templatechooser']);
    }
}
