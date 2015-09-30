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
    public function testBasicMakeSafe()
    {
        // basic
        $input = "this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this is a uis s", Str::makeSafe($input));
    }
    public function testBasicMakeSafeUpperCases()
    {
        // basic
        $input = "This Is A ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("This Is A uis s", Str::makeSafe($input));
    }
    public function testStrictMakeSafe()
    {
        //strict
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this-is-a-uis-s", Str::makeSafe($input, true));
    }
    public function testStrictMakeSafeUpperCases()
    {
        //strict
        $input = ";This Is A ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this-is-a-uis-s", Str::makeSafe($input, true));
    }
    public function testExtraCharsMakeSafe()
    {
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals(";this-is-a-uis-s", Str::makeSafe($input, true, ';'));
    }
    public function testExtraCharsNotStrictMakeSafe()
    {
        $input = "; IS this a ƃuıɹʇs ʇsǝʇ \o/";
        $this->assertEquals("; IS this a uis s \o/", Str::makeSafe($input, false, ';\o/'));
    }
    public function testGermanMakeSafe()
    {
        // German
        $input = "Ich lässt öfter mit Vätern in einer kleinen Straße";
        $this->assertEquals("ich-laesst-oefter-mit-vaetern-in-einer-kleinen-strasse", Str::makeSafe($input, true, ';'));
    }
    public function testFrenchMakeSafe()
    {
        // French
        $input = "Et le proxénète s'est avancé à la barre";
        $this->assertEquals("et-le-proxenete-sest-avance-a-la-barre", Str::makeSafe($input, true, ';'));
    }
    public function testFrenchMakeSafeNotStrictKeepsUppercase()
    {
        // French
        $input = "Et le proxénète s'est Avancé à la Barre";
        $this->assertEquals("Et le proxenete sest Avance a la Barre", Str::makeSafe($input, false));
    }
    public function testSwedishMakeSafe()
    {
        // Swedish
        $input = "Skämt åsido satan vilket uruselt tillvägagångsätt";
        $this->assertEquals("skaemt-aasido-satan-vilket-uruselt-tillvaegagaangsaett", Str::makeSafe($input, true, ';'));
    }

    public function testReplaceFirst()
    {
        $input = "this is a test string this is a test string";
        $this->assertEquals("one is a test string this is a test string", Str::replaceFirst('this', 'one', $input));
    }
}
