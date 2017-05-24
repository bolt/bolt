<?php

namespace Bolt\Tests\Provider;

use Bolt\Tests\BoltUnitTest;
use Cocur\Slugify\Slugify;

/**
 * Class to test Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider used in $app['slugify'].
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SlugifyProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Slugify::class, $app['slugify']);

        $slug = 'This is a title';
        $this->assertEquals('this-is-a-title', $app['slugify']->slugify($slug));

        $slug = 'Nürnberg Bratwürste';
        $this->assertEquals('nuernberg-bratwuerste', $app['slugify']->slugify($slug));
    }
}
