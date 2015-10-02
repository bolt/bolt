<?php
namespace Bolt\Tests\Session;

use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Session/Serializer/NativeSerializer.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NativeSerializerTest extends BoltUnitTest
{
    public function testSerialize()
    {
        $cornFlakes = new NativeSerializer();
        $captCrunch = $cornFlakes->serialize(['milk' => 'bowl']);

        $this->assertSame($captCrunch, 'a:1:{s:4:"milk";s:4:"bowl";}');
    }

    public function testUnserialize()
    {
        $cornFlakes = new NativeSerializer();
        $weetBix = $cornFlakes->unserialize('a:1:{s:4:"milk";s:4:"bowl";}');

        $this->assertArrayHasKey('milk', $weetBix);
        $this->assertSame('bowl', $weetBix['milk']);
    }
}
