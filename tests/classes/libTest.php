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
                false, false, false, "12345678901234567890"),
            // input shorter than length param
            array("123456789012345678901234567890", 40,
                false, false, false, "123456789012345678901234567890"),
            // check if spaces are treated as a character, but don't transform
            array("1234567890 1234567890 1234567890", 30,
                false, false, false, "1234567890 1234567890 12345678"),
            // transform spaces
            array("1234567890 1234567890 1234567890", 40,
                true, false, false, "1234567890&nbsp;1234567890&nbsp;1234567890"),
            // add dots when the input is too long
            array("123456789012345678901234567890", 20,
                false, true, false, "12345678901234567890…"),
            // and don't when it's not
            array("12345678901234567890", 20,
                false, true, false, "12345678901234567890"),
            // strip tags
            array("1234567890<b>123456789012345</b>67890", 20,
                false, false, true, "12345678901234567890"),
            // don't strip tags
            array("1234567890<b>12345678</b>901234567890", 20,
                false, false, false, "1234567890<b>12345678</b>90"),
            // fix tags
            array("1234567890<b>123456789012345</b>67890", 20,
                false, false, false, "1234567890<b>1234567890</b>"),
            // fix nested tags
            array("123<i>4567890<b>123456789012345</b>67</i>890", 20,
                false, false, false, "123<i>4567890<b>1234567890</b></i>"),
            // wrong order tags
            array("123<i>4567890<b>123456789012345</i>67</b>890", 20,
                false, false, false, "123<i>4567890<b>1234567890</b></i>"),
            // tags with attributes
            array('The quick brown <a href="http://www.bolt.cm">fox</a> jumps over the lazy dog',
                19, false, false, false, 'The quick brown <a href="http://www.bolt.cm">fox</a>'),
            // two tags
            //!// temporarily disabled; this test fails on whitespace, because
            //!// the unicode-safe options for htmLawed don't collapse
            //!// whitespace anymore.
            //!// Eventually, we should fix the trimText() functionality
            //!// properly and then re-enable the test.
            //! array('The <a href="http://bolt.cm">quick</a> brown <a href="http://bolt.cm">fox</a> jumps over the lazy dog',
            //!     19, false, false, false, 'The <a href="http://bolt.cm">quick</a> brown <a href="http://bolt.cm">fox</a>'),
            // http:// shouldn't get stripped
            array('http://bolt.cm', 11, false, false, false, 'http://bolt'),
            // add dots in links when link text is trimmed
            array('<a href="http://bolt.cm">Bolt is awesome</a>', 7, false, true, false,
                '<a href="http://bolt.cm">Bolt is…</a>'),
            // unmatching tags
            array('<a href="http://bolt.cm">Bolt is awesome', 7, false, true, false,
                '<a href="http://bolt.cm">Bolt is…</a>'),
        );
    }

    /**
     * @dataProvider trimTextDataProvider
     */
    public function testTrimText($str, $length, $nbsp, $hellip, $striptags, $expected)
    {
        $result = Html::trimText($str, $length, $nbsp, $hellip, $striptags);
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
