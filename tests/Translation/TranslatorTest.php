<?php
namespace Bolt\Tests\Translation;

use Bolt\Tests\BoltUnitTest;
use Bolt\Translation\Translator;

/**
 * Class to test src/Translation/Translator.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TranslatorTest extends BoltUnitTest
{
    public function testSimpleTranslate()
    {
        $app = $this->getApp();
        $app->boot();
        $this->assertEquals('testing', Translator::__('testing'));
    }

    public function testArrayTranslate()
    {
        $app = $this->getApp();
        $app->boot();
        $this->assertEquals('testkey.testanother', Translator::__(array('testkey', 'testanother')));
    }

    public function testMessageTranslate()
    {
        $app = $this->getApp();
        $app->boot();
        $this->assertEquals('JavaScript disabled', Translator::__('generic.noscript.headline'));
    }

    public function testContenttypes()
    {
        $app = $this->getApp();
        $app->boot();
        $this->assertEquals('Delete Page', Translator::__('contenttypes.generic.delete', array('%contenttype%' => 'pages')));
        $this->assertEquals('Delete Showcase', Translator::__('contenttypes.generic.delete', array('%contenttype%' => 'showcase')));
    }

    public function testTransDefault()
    {
        $app = $this->getApp();
        $app->boot();
        $this->assertEquals('Here', Translator::__('nonexistentkey', array('DEFAULT' => 'Here')));
    }

    // No translations currently use this, so here as a placeholder
    public function testTransNumber()
    {
        $app = $this->getApp();
        $app->boot();
        $this->assertEquals('nonexistentkey', Translator::__('nonexistentkey', array('NUMBER' => 5)));
    }
}
