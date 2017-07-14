<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Stack;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\AdminRuntime;
use Monolog\Logger;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class to test Bolt\Twig\Runtime\AdminRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AdminRuntimeTest extends BoltUnitTest
{
    public function testBuic()
    {
        $handler = $this->getAdminRuntime();

        $this->assertSame('buid-1', $handler->buid());
        $this->assertSame('buid-2', $handler->buid());
        $this->assertSame('buid-3', $handler->buid());
    }

    public function testAddDataEmpty()
    {
        $app = $this->getApp();
        $handler = $this->getAdminRuntime();

        $handler->addData('', '');
        $this->assertEmpty($app['jsdata']);
    }

    public function testAddDataValid()
    {
        $app = $this->getApp();
        $handler = $this->getAdminRuntime();

        $handler->addData('drop.bear', 'Johno');
        $this->assertArrayHasKey('drop', $app['jsdata']);
        $this->assertArrayHasKey('bear', $app['jsdata']['drop']);
        $this->assertSame('Johno', $app['jsdata']['drop']['bear']);
    }

    public function testIsChangelogEnabled()
    {
        $app = $this->getApp();
        $handler = $this->getAdminRuntime();

        $app['config']->set('general/changelog/enabled', false);
        $result = $handler->isChangelogEnabled();
        $this->assertFalse($result);

        $app['config']->set('general/changelog/enabled', true);
        $result = $handler->isChangelogEnabled();
        $this->assertTrue($result);
    }

    public function testStackable()
    {
        $app = $this->getApp();
        $stack = $this->getMockStack();
        $stack
            ->expects($this->once())
            ->method('isStackable')
        ;
        $this->setService('stack', $stack);

        $handler = $this->getAdminRuntime();

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
        $this->setService('stack', $stack);

        $handler = $this->getAdminRuntime();

        $handler->stack(['other', 'document']);
        $handler->stack('other, document');
        $handler->stack('');
    }

    public function testLogLevelString()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->logLevel('debug');
        $this->assertSame('debug', $result);
    }

    public function testLogLevelNumeric()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->logLevel(Logger::ALERT);
        $this->assertSame('Alert', $result);

        $result = $handler->logLevel(Logger::CRITICAL);
        $this->assertSame('Critical', $result);

        $result = $handler->logLevel(Logger::DEBUG);
        $this->assertSame('Debug', $result);

        $result = $handler->logLevel(Logger::EMERGENCY);
        $this->assertSame('Emergency', $result);

        $result = $handler->logLevel(Logger::ERROR);
        $this->assertSame('Error', $result);

        $result = $handler->logLevel(Logger::INFO);
        $this->assertSame('Info', $result);

        $result = $handler->logLevel(Logger::NOTICE);
        $this->assertSame('Notice', $result);

        $result = $handler->logLevel(Logger::WARNING);
        $this->assertSame('Warning', $result);
    }

    public function testLogLevelInvalid()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->logLevel(42);
        $this->assertSame(42, $result);
    }

    public function testTransNoArgs()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->trans();
        $this->assertNull($result);
    }

    public function testTransArgsOne()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->trans('general.about');
        $this->assertSame('About', $result);
    }

    public function testTransArgsTwo()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->trans('general.bolt-welcome-new-site', ['%USER%' => 'Koala']);
        $this->assertSame('Welcome to your new Bolt site, Koala.', $result);
    }

    public function testTransArgsThree()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->trans('general.phrase.access-denied-logged-out', [], 'messages');
        $this->assertSame('You have been logged out.', $result);
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
        $this->setService('translator', $trans);

        $handler = $this->getAdminRuntime();

        $result = $handler->trans('contenttypes.generic.delete', ['%contenttype%' => 'page'], 'messages', 'de_DE');
        $this->assertSame('Page löschen', $result);
    }

    public function testRandomQuote()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->randomQuote();
        $this->assertRegExp('#<cite>#', $result);
    }

    public function testYmllink()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->ymllink(' config.yml');
        $this->assertSame(' <a href="/bolt/file/edit/config/config.yml">config.yml</a>', $result);
    }

    public function testYmlLinkMultiple()
    {
        $handler = $this->getAdminRuntime();

        $input = 'Please check your contenttypes.yml and your theme.yml file.';
        $expected = 'Please check your <a href="/bolt/file/edit/config/contenttypes.yml">contenttypes.yml</a> ' .
            'and your <a href="/bolt/file/edit/config/theme.yml">theme.yml</a> file.';

        $result = $handler->ymllink($input);
        $this->assertSame($expected, $result);
    }

    public function testHattr()
    {
        $handler = $this->getAdminRuntime();

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
        $handler = $this->getAdminRuntime();

        $result = $handler->hclass('first second', false);
        $this->assertSame(' class="first second"', $result);
    }

    public function testHclassStringRaw()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->hclass('first second', true);
        $this->assertSame('first second', $result);
    }

    public function testHclassArrayNotRaw()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->hclass(['first', 'second'], false);
        $this->assertSame(' class="first second"', $result);
    }

    public function testHclassArrayRaw()
    {
        $handler = $this->getAdminRuntime();

        $result = $handler->hclass(['first', 'second'], true);
        $this->assertSame('first second', $result);
    }

    /**
     * @return AdminRuntime
     */
    protected function getAdminRuntime()
    {
        $app = $this->getApp();

        return new AdminRuntime($app['config'], $app['stack'], $app['url_generator'], $app);
    }

    /**
     * @return Stack|MockObject
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
