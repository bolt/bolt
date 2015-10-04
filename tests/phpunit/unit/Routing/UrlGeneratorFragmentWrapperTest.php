<?php

namespace Bolt\Tests\Routing;

use Bolt\Routing\LazyUrlGenerator;
use Bolt\Routing\UrlGeneratorFragmentWrapper;
use Bolt\Tests\BoltUnitTest;
use Silex\Route;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class UrlGeneratorFragmentWrapperTest extends BoltUnitTest
{
    public function testUrlGeneratorInterface()
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo/bar'));
        $parent = new UrlGenerator($collection, new RequestContext());
        $generator = new UrlGeneratorFragmentWrapper($parent);

        $this->assertInstanceOf('\Symfony\Component\Routing\Generator\UrlGeneratorInterface', $generator);

        return $generator;
    }

    /**
     * @depends testUrlGeneratorInterface
     *
     * @param UrlGeneratorInterface $generator
     */
    public function testUrlGenerationWithFragment($generator)
    {
        $path = $generator->generate('foo', ['#' => 'bolt']);
        $this->assertSame('/foo/bar#bolt', $path);
        $path = $generator->generate('foo', ['hello' => 'world', '#' => 'bolt']);
        $this->assertSame('/foo/bar?hello=world#bolt', $path);
    }

    /**
     * @depends testUrlGeneratorInterface
     *
     * @param UrlGeneratorInterface $generator
     */
    public function testRequestContext($generator)
    {
        $generator->setContext(new RequestContext('/bolt'));
        $this->assertSame('/bolt', $generator->getContext()->getBaseUrl());
    }

    /**
     * @depends testUrlGeneratorInterface
     *
     * @param ConfigurableRequirementsInterface $generator
     */
    public function testConfigurableRequirements($generator)
    {
        $generator->setStrictRequirements(true);
        $this->assertTrue($generator->isStrictRequirements());
        $generator->setStrictRequirements(false);
        $this->assertFalse($generator->isStrictRequirements());
        $generator->setStrictRequirements(null);
        $this->assertNull($generator->isStrictRequirements());
    }

    public function testNonConfigurableRequirements()
    {
        $generator = new UrlGeneratorFragmentWrapper(new LazyUrlGenerator(function () {}));
        $generator->setStrictRequirements(true);
        $this->assertNull($generator->isStrictRequirements());
    }
}
