<?php
namespace Bolt\Tests\Helper;

use Bolt\Helpers\String;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Helper/String.
 *
 * @author Ross Riley <riley.ross@gmail.com>
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

        // German
        $input = "Ich lässt öfter mit Vätern in einer kleinen Straße";
        $this->assertEquals("ich-laesst-oefter-mit-vaetern-in-einer-kleinen-strasse", String::makeSafe($input, true, ';'));

        // French
        $input = "Et le proxénète s'est avancé à la barre";
        $this->assertEquals("et-le-proxenete-sest-avance-a-la-barre", String::makeSafe($input, true, ';'));

        // Swedish
        $input = "Skämt åsido satan vilket uruselt tillvägagångsätt";
        $this->assertEquals("skaemt-asido-satan-vilket-uruselt-tillvaegagangsaett", String::makeSafe($input, true, ';'));
    }

    public function testReplaceFirst()
    {
        $input = "this is a test string this is a test string";
        $this->assertEquals("one is a test string this is a test string", String::replaceFirst('this', 'one', $input));
    }
}
