<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\ShowPackage;

/**
 * Class to test src/Composer/Action/ShowPackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ShowPackageTest extends ActionUnitTest
{
    public function testAvailable()
    {
        $app = $this->getApp();

        $result = $app['extend.action']['show']->execute('available', 'gawain/clippy', '~2.0');
        $this->assertArrayHasKey('gawain/clippy', $result);
    }

    /**
     * This test has been disabled at 2015-07-18 due to problems with Travis & composer
     *
     * @see https://github.com/bolt/bolt/issues/3829
     */
//     public function testRootEnquiry()
//     {
//         $app = $this->getApp();

//         $result = $app['extend.action']['show']->execute('available', 'bolt/bolt', '~2.0', true);
//         $this->assertArrayHasKey('bolt/bolt', $result);
//     }
}
