<?php
namespace Bolt\Tests\Session;

use Bolt\Session\Generator\NativeGenerator;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Session/Generator/NativeGenerator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NativeGeneratorTest extends BoltUnitTest
{
    public function testGenerateId()
    {
        $fooFighters = new NativeGenerator();
        $daveGrohl = $fooFighters->generateId();

        $this->assertNotSame('Nirvana', $daveGrohl);
        $this->assertSame(32, strlen($daveGrohl));
    }
}
