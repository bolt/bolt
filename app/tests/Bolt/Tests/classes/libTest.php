<?php

// namespace <none> * sad panda face *
// (which means no autoloading..)
require_once dirname(dirname(dirname(dirname(__DIR__)))) .'/classes/lib.php';

class libTest extends \PHPUnit_Framework_TestCase {

    /**
     * The data provider for trim text. Should contain all possible type of
     * crap which can be thrown at it.
     */
    public static function trimTextDataProvider(){
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
            array("123<i>4567890<b>12<p>345678</p>9012345</b>67</i>890", 20,
                false, false, false, "123<i>4567890<b>12<p>345678</p>90</b></i>"),
            // tags with attributes
            array('The quick brown <a href="http://bolt.cm">fox</a> jumps over the lazy dog',
                19, false, false, false, 'The quick brown <a href="http://www.bolt.cm">fox</a>'),
            // http:// shouldn't get stripped
            array('http://bolt.cm', 20, false, false, false, 'http://bolt.cm'),
        );
    }

    /**
     * @dataProvider trimTextDataProvider
     */
    public function testTrimText($str, $length, $nbsp, $hellip, $striptags, $expected){
        $result = trimText($str, $length, $nbsp, $hellip, $striptags);
        $this->assertEquals($expected, $result);
    }
}