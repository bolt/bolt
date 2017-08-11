<?php

namespace Bolt\Tests\Response;

use Bolt\Collection\MutableBag;
use Bolt\Response\TemplateView;
use PHPUnit\Framework\TestCase as TestCase;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class TemplateViewTest extends TestCase
{
    public function testTemplate()
    {
        $view = new TemplateView('foo.twig');

        $this->assertSame('foo.twig', $view->getTemplate());

        $view->setTemplate('bar.twig');

        $this->assertSame('bar.twig', $view->getTemplate());
    }

    public function testContext()
    {
        $view = new TemplateView('', ['foo' => 'bar']);

        $this->assertInstanceOf(MutableBag::class, $view->getContext());
        $this->assertSame('bar', $view->getContext()->get('foo'));

        $view->setContext(['hello' => 'world']);
        $this->assertInstanceOf(MutableBag::class, $view->getContext());
        $this->assertEquals(['hello' => 'world'], $view->getContext()->toArray());

        $view->setContext(MutableBag::from(['foo' => 'bar']));
        $this->assertEquals(['foo' => 'bar'], $view->getContext()->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTemplateBadConstructor()
    {
        new TemplateView(false);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTemplateBadSetter()
    {
        $view = new TemplateView('');

        $view->setTemplate(false);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContextBadConstructor()
    {
        new TemplateView('', false);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContextBadSetter()
    {
        $view = new TemplateView('');

        $view->setContext(false);
    }
}
