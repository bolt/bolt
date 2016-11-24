<?php

namespace Bolt\Tests\Twig;

use Bolt\Stack;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\AdminHandler;
use Silex\Translator;

/**
 * Class to test Bolt\Twig\Handler\AdminHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AdminHandlerTest extends BoltUnitTest
{
    public function testBuic()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $this->assertSame('buid-1', $handler->buid());
        $this->assertSame('buid-2', $handler->buid());
        $this->assertSame('buid-3', $handler->buid());
    }

    public function testAddDataEmpty()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $handler->addData('', '');
        $this->assertEmpty($app['jsdata']);
    }

    public function testAddDataValid()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $handler->addData('drop.bear', 'Johno');
        $this->assertArrayHasKey('drop', $app['jsdata']);
        $this->assertArrayHasKey('bear', $app['jsdata']['drop']);
        $this->assertSame('Johno', $app['jsdata']['drop']['bear']);
    }

    public function testStackable()
    {
        $app = $this->getApp();
        $stack = $this->getMockStack();
        $stack
            ->expects($this->once())
            ->method('isStackable')
        ;
        $app['stack'] = $stack;

        $handler = new AdminHandler($app);

        $handler->testStackable('koala.jpg');
    }

    public function testStack()
    {
        $app = $this->getApp();

        $stack = $this->getMockStack();
        $stack
            ->expects($this->exactly(3))
            ->method('getList')
            ->withConsecutive(
                [['other', 'document']],
                [['other', 'document']],
                [[]]
            )
        ;
        $app['stack'] = $stack;

        $handler = new AdminHandler($app);

        $handler->stack(['other', 'document']);
        $handler->stack('other, document');
        $handler->stack('');
    }

    public function testLogLevelString()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->logLevel('debug');
        $this->assertSame('debug', $result);
    }

    public function testLogLevelNumeric()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->logLevel(\Monolog\Logger::ALERT);
        $this->assertSame('Alert', $result);

        $result = $handler->logLevel(\Monolog\Logger::CRITICAL);
        $this->assertSame('Critical', $result);

        $result = $handler->logLevel(\Monolog\Logger::DEBUG);
        $this->assertSame('Debug', $result);

        $result = $handler->logLevel(\Monolog\Logger::EMERGENCY);
        $this->assertSame('Emergency', $result);

        $result = $handler->logLevel(\Monolog\Logger::ERROR);
        $this->assertSame('Error', $result);

        $result = $handler->logLevel(\Monolog\Logger::INFO);
        $this->assertSame('Info', $result);

        $result = $handler->logLevel(\Monolog\Logger::NOTICE);
        $this->assertSame('Notice', $result);

        $result = $handler->logLevel(\Monolog\Logger::WARNING);
        $this->assertSame('Warning', $result);
    }

    public function testLogLevelInvalid()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->logLevel(42);
        $this->assertSame(42, $result);
    }

    public function testTransNoArgs()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->trans([], 0);
        $this->assertNull($result);
    }

    public function testTransArgsOne()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->trans(['general.about'], 1);
        $this->assertSame('About', $result);
    }

    public function testTransArgsTwo()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->trans(['contenttypes.generic.delete', ['%contenttype%' => 'Page']], 2);
        $this->assertSame('Delete Page', $result);
    }

    public function testTransArgsThree()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->trans(['contenttypes.pages.group.content', [], 'contenttypes'], 3);
        $this->assertSame('Content', $result);
    }

    public function testTransArgsFour()
    {
        $app = $this->getApp();
        $trans = $this->getMockBuilder(\Silex\Translator::class)
            ->setMethods(['trans'])
            ->setConstructorArgs([$app, $app['translator.message_selector']])
            ->getMock()
        ;
        $trans
            ->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnValue('Page löschen'))
        ;
        $app['translator'] = $trans;

        $handler = new AdminHandler($app);

        $result = $handler->trans(['contenttypes.generic.delete', ['%contenttype%' => 'page'], 'messages', 'de_DE'], 4);
        $this->assertSame('Page löschen', $result);
    }

    public function testRandomQuote()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->randomQuote();
        $this->assertRegExp('#<cite>#', $result);
    }

    public function testYmllinkSafe()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->ymllink(' config.yml', true);
        $this->assertNull($result);
    }

    public function testYmllink()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->ymllink(' config.yml', false);
        $this->assertSame(' <a href="/bolt/file/edit/config/config.yml">config.yml</a>', $result);
    }

    public function testHattr()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $attributes = [
            'class'        => 'info-pop fa fa-info-circle',
            'data-content' => ['gum', 'leaf'],
            'data-title'   => 'clippy',
            'checked'      => true,
            'name+id'      => 'koala',
        ];

        $result = $handler->hattr($attributes);
        $this->assertSame(' class="info-pop fa fa-info-circle" data-content="[&quot;gum&quot;,&quot;leaf&quot;]" data-title="clippy" checked name="koala" id="koala"', $result);
    }

    public function testHclassStringNotRaw()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->hclass('first second', false);
        $this->assertSame(' class="first second"', $result);
    }

    public function testHclassStringRaw()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->hclass('first second', true);
        $this->assertSame('first second', $result);
    }

    public function testHclassArrayNotRaw()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->hclass(['first', 'second'], false);
        $this->assertSame(' class="first second"', $result);
    }

    public function testHclassArrayRaw()
    {
        $app = $this->getApp();
        $handler = new AdminHandler($app);

        $result = $handler->hclass(['first', 'second'], true);
        $this->assertSame('first second', $result);
    }

    /**
     * @return Stack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockStack()
    {
        $app = $this->getApp();

        return $this->getMockBuilder(Stack::class)
            ->setMethods([])
            ->setConstructorArgs([$app['filesystem.matcher'], $app['users'], $app['session'], []])
            ->getMock()
            ;
    }
}
