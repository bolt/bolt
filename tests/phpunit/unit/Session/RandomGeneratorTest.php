<?php
namespace Bolt\Tests\Session;

use Bolt\Session\Generator\RandomGenerator;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Session/Generator/RandomGenerator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RandomGeneratorTest extends BoltUnitTest
{
    public function testConstructor()
    {
        $app = $this->getApp();
        $fooFighters = new RandomGenerator($app['randomgenerator']);

        $this->assertObjectHasAttribute('generator',  $fooFighters);
        $this->assertObjectHasAttribute('length',     $fooFighters);
        $this->assertObjectHasAttribute('characters', $fooFighters);
    }

    public function testGenerateId()
    {
        $app = $this->getApp();
        $fooFighters = new RandomGenerator($app['randomgenerator'], 42);
        $daveGrohl = $fooFighters->generateId();

        $this->assertNotSame('Nirvana', $daveGrohl);
        $this->assertSame(42, strlen($daveGrohl));
    }
}
