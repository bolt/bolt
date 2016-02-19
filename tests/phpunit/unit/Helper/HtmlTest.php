<?php
namespace Bolt\Tests\Helper;

use Bolt\Helpers\Html;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Helper/Html.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class HtmlTest extends BoltUnitTest
{
    public function testTrimText()
    {
        // Simple text
        $input = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.';
        $this->assertEquals('Lorem ipsum', Html::trimText($input, 11, false));
        $this->assertEquals('Lorem ipsum…', Html::trimText($input, 12, true));

        // Make sure tags are stripped
        $input = 'Lorem <strong>ipsum</strong> dolor sit amet, consectetur adipisicing elit.';
        $this->assertEquals('Lorem ipsum', Html::trimText($input, 11, false));
    }

    public function testDecorateTT()
    {
        $input = 'Lorem `ipsum` dolor.';
        $this->assertEquals('Lorem <tt>ipsum</tt> dolor.', Html::decorateTT($input));
    }

    public function testIsURL()
    {
        $this->assertTrue(Html::isURL('example.org'));
        $this->assertTrue(Html::isURL('example.org/'));
        $this->assertTrue(Html::isURL('www.example.org'));
        $this->assertTrue(Html::isURL('https://example.org'));
        $this->assertTrue(Html::isURL('https://example.org/'));
        $this->assertTrue(Html::isURL('http://foo.com/blah_blah'));
        $this->assertTrue(Html::isURL('http://foo.com/blah_blah/'));
        $this->assertTrue(Html::isURL('http://foo.com/blah_blah_(wikipedia)'));
        $this->assertTrue(Html::isURL('http://foo.com/blah_blah_(wikipedia)_(again)'));
        $this->assertTrue(Html::isURL('http://www.example.com/wpstyle/?p=364'));
        $this->assertTrue(Html::isURL('https://www.example.com/foo/?bar=baz&inga=42&quux'));
        $this->assertTrue(Html::isURL('http://142.42.1.1/'));
        $this->assertTrue(Html::isURL('http://142.42.1.1:8080/'));
        $this->assertTrue(Html::isURL('http://j.mp'));
        $this->assertTrue(Html::isURL('ftp://foo.bar/baz'));
        $this->assertTrue(Html::isURL('http://1337.net'));
        $this->assertTrue(Html::isURL('http://a.b-c.de'));
        $this->assertTrue(Html::isURL('http://223.255.255.254'));

        $this->assertFalse(Html::isURL('http://'));
        $this->assertFalse(Html::isURL('https://'));
        $this->assertFalse(Html::isURL('//'));
        $this->assertFalse(Html::isURL('//a'));
        $this->assertFalse(Html::isURL('http:/example.org'));
        $this->assertFalse(Html::isURL('http:example.org'));
    }

    public function testAddScheme()
    {
        $this->assertEquals('http://example.org', Html::addScheme('example.org'));
        $this->assertEquals('http://example.org', Html::addScheme('http://example.org'));
        $this->assertEquals('https://example.org', Html::addScheme('https://example.org'));
        $this->assertEquals('mailto:bob@bolt.cm', Html::addScheme('mailto:bob@bolt.cm'));
    }
}
