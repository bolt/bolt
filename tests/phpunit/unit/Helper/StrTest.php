<?php
namespace Bolt\Tests\Helper;

use Bolt\Helpers\Str;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Helper/Str.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StrTest extends BoltUnitTest
{
    public function testMakeSafe()
    {
        // basic
        $input = "this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this is a uis s", Str::makeSafe($input));

        //strict
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this-is-a-uis-s", Str::makeSafe($input, true));

        // extra chars
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals(";this-is-a-uis-s", Str::makeSafe($input, true, ';'));

        // German
        $input = "Ich lässt öfter mit Vätern in einer kleinen Straße";
        $this->assertEquals("ich-laesst-oefter-mit-vaetern-in-einer-kleinen-strasse", Str::makeSafe($input, true, ';'));

        // French
        $input = "Et le proxénète s'est avancé à la barre";
        $this->assertEquals("et-le-proxenete-sest-avance-a-la-barre", Str::makeSafe($input, true, ';'));

        // Swedish
        $input = "Skämt åsido satan vilket uruselt tillvägagångsätt";
        $this->assertEquals("skaemt-asido-satan-vilket-uruselt-tillvaegagangsaett", Str::makeSafe($input, true, ';'));
    }

    public function testReplaceFirst()
    {
        $input = "this is a test string this is a test string";
        $this->assertEquals("one is a test string this is a test string", Str::replaceFirst('this', 'one', $input));
    }
}
