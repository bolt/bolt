<?php

namespace Bolt\Tests\Asset;

use Bolt\Asset\Injector;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Collection\MutableBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Bolt\Asset\Injector
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class InjectorTest extends TestCase
{
    public function providerTarget()
    {
        $o = new \ReflectionClass(Target::class);
        $constants = MutableBag::from(array_keys($o->getConstants()))
            ->filter(function ($k, $v) {
                return strpos($v, 'WIDGET') === false;
            })
            ->map(function ($k, $v) {
                return [$v];
            })
        ;

        return $constants->toArray();
    }

    /**
     * @dataProvider providerTarget
     *
     * @param string $constant
     */
    public function testMap($constant)
    {
        $constant = constant('Bolt\Asset\Target::' . $constant);
        $injector = new Injector();

        self::assertArrayHasKey($constant, $injector->getMap());
    }

    /**
     * @dataProvider providerTarget
     *
     * @param string $constant
     */
    public function testInject($constant)
    {
        $expected = file_get_contents(__DIR__ . '/../Fixtures/AssetInjector/result.' . $constant . '.html');
        $constant = constant('Bolt\Asset\Target::' . $constant);
        $injector = new Injector();
        $asset = Snippet::create()
            ->setCallback('koala')
            ->setLocation($constant)
        ;
        $response = new Response($this->getHtml());
        $injector->inject($asset, $constant, $response);

        self::assertSame($expected, $response->getContent());
    }

    /**
     * @dataProvider providerTarget
     *
     * @param string $constant
     */
    public function testInjectInvalidLocation($constant)
    {
        $constant = constant('Bolt\Asset\Target::' . $constant);
        $injector = new Injector();
        $asset = Snippet::create()
            ->setCallback('koala')
            ->setLocation($constant)
        ;
        $html = $this->getHtml();
        $response = new Response($html);
        $injector->inject($asset, '', $response);

        self::assertSame($html . "koala\n", $response->getContent());
    }

    /**
     * @dataProvider providerTarget
     *
     * @param string $constant
     */
    public function testInjectEmptyHtml($constant)
    {
        $constant = constant('Bolt\Asset\Target::' . $constant);
        $injector = new Injector();
        $asset = Snippet::create()
            ->setCallback('koala')
            ->setLocation($constant)
        ;
        $response = new Response();
        $injector->inject($asset, $constant, $response);

        self::assertSame("koala\n", $response->getContent());
    }

    /**
     * @dataProvider providerTarget
     *
     * @param string $constant
     */
    public function testInjectTagSoup($constant)
    {
        $constant = constant('Bolt\Asset\Target::' . $constant);
        $injector = new Injector();
        $asset = Snippet::create()
            ->setCallback('koala')
            ->setLocation($constant)
        ;
        //$html = $this->getHtml();
        $response = new Response('<blink>');
        $injector->inject($asset, $constant, $response);

        self::assertSame("<blink>koala\n", $response->getContent());
    }

    protected function getHtml()
    {
        return file_get_contents(__DIR__ . '/../Fixtures/AssetInjector/index.html');
    }
}
