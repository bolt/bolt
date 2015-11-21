<?php
namespace Bolt\Tests\Provider;

use Bolt\Tests\BoltUnitTest;
use Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider;

/**
 * Class to test Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider used in $app['slugify']
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SlugifyProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new SlugifyServiceProvider();
        $app->register($provider);
        $this->assertInstanceOf('Cocur\Slugify\Slugify', $app['slugify']);
        $app->boot();

        $slug = 'This is a title';
        $this->assertEquals('this-is-a-title', $app['slugify']->slugify($slug));

        $slug = 'Nürnberg Bratwürste';
        $this->assertEquals('nuernberg-bratwuerste', $app['slugify']->slugify($slug));
    }
}
