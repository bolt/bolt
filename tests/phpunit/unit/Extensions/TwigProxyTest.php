<?php
namespace Bolt\Tests\Extensions;

use Bolt\Extensions\TwigProxy;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Events/StorageEvent.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TwigProxyTest extends BoltUnitTest
{
    public function testSetup()
    {
        $twig = new TwigProxy('mytest');

        $func = $this->getMock('\Twig_SimpleFunction', null, ['test', function () {}]);
        $filter = $this->getMock('\Twig_SimpleFilter', null, ['testfilter', function () {}]);
        $twig->addTwigFunction($func);
        $twig->addTwigFilter($filter);

        $this->assertEquals(1, count($twig->getFunctions()));
        $this->assertEquals(1, count($twig->getFilters()));
        $this->assertEquals('mytest', $twig->getName());
    }
}
