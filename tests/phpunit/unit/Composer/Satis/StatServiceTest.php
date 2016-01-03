<?php
namespace Bolt\Tests\Extensions;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/Satis/StatService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StatServiceTest extends BoltUnitTest
{
    public function testSetup()
    {
        $app = $this->getApp();
        $stat = $this->getMock('Bolt\Composer\Satis\StatService', ['recordInstall'], [$app['guzzle.client'], $app['logger.system'], $app['extend.site']]);
        $stat->expects($this->once())
            ->method('recordInstall')
            ->with('mytest', '1.0.0');

        $response = $stat->recordInstall('mytest', '1.0.0');
    }
}

namespace Bolt\Extensions;

// Left for info, this mock function is called on this test
// function file_get_contents($url)
// {
//     return $url;
// }

