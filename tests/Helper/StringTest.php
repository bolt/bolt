<?php
namespace Bolt\Tests\Helper;

use Bolt\Tests\BoltUnitTest;
use Bolt\Helpers\String;

/**
 * Class to test src/Helper/String.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StringTest extends BoltUnitTest
{

    public function testMakeSafe()
    {
        // basic
        $input = "this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this is a uis s", String::makeSafe($input));

        //strict
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this-is-a-uis-s", String::makeSafe($input, true));

        // extra chars
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals(";this-is-a-uis-s", String::makeSafe($input, true, ';'));
    }

    public function testReplaceFirst()
    {
        $input = "this is a test string this is a test string";
        $this->assertEquals("one is a test string this is a test string", String::replaceFirst('this', 'one', $input));
    }
}
