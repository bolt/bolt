<?php

use Bolt\Library as Lib;
use Bolt\Helpers\Html;
use Bolt\Translation\Translator as Trans;

class libTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The data provider for trim text. Should contain all possible type of
     * crap which can be thrown at it.
     */
    public static function trimTextDataProvider()
    {
        return array(
            // all ok case
            array("123456789012345678901234567890", 20,
                false, "12345678901234567890"),
            // input shorter than length param
            array("123456789012345678901234567890", 40,
                false, "123456789012345678901234567890"),
            // check if spaces are treated as a character, but don't transform
            array("1234567890 1234567890 1234567890", 30,
                false, "1234567890 1234567890 12345678"),
            // add dots when the input is too long
            array("123456789012345678901234567890", 20,
                true, "12345678901234567890…"),
            // and don't when it's not
            array("12345678901234567890", 20,
                true, "12345678901234567890"),
            // strip tags
            array("1234567890<b>123456789012345</b>67890", 20,
                false, "12345678901234567890"),
            // http:// shouldn't get stripped
            array('http://bolt.cm', 11, false, false, false, 'http://bolt'),
        );
    }

    /**
     * @dataProvider trimTextDataProvider
     */
    public function testTrimText($str, $length, $hellip, $expected)
    {
        $result = Html::trimText($str, $length, $hellip);
        $this->assertEquals($expected, $result);
    }


    public static function getExtensionDataProvider()
    {
        return array(
                array('foobar.baz', 'baz'),
                array('foobar.baz.quux', 'quux'),
                array('foobar', ''),
                array('foo.bar/baz.quux', 'quux'),
                array('/foo.bar/baz.quux', 'quux'),
                array('foo.bar/baz.', ''),
            );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetExtension($filename, $expected)
    {
        $actual = Lib::getExtension($filename);
        $this->assertEquals($expected, $actual);
    }
}
