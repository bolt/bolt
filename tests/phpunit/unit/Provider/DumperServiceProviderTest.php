<?php

namespace Bolt\Tests\Provider;

use Bolt\Provider\DumperServiceProvider;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Symfony\Component\VarDumper\Cloner;
use Symfony\Component\VarDumper\Dumper;

/**
 * @covers \Bolt\Provider\DumperServiceProvider
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DumperServiceProviderTest extends TestCase
{
    public function testProvider()
    {
        $app = new Application(['debug' => true]);
        $provider = new DumperServiceProvider();
        $provider->register($app);

        $this->assertArrayHasKey('dump', $app);
        $this->assertArrayHasKey('dumper', $app);
        $this->assertArrayHasKey('dumper.cli', $app);
        $this->assertArrayHasKey('dumper.html', $app);
        $this->assertArrayHasKey('dumper.cloner', $app);

        $this->assertInstanceOf(Dumper\CliDumper::class, $app['dumper']);
        $this->assertInstanceOf(Dumper\CliDumper::class, $app['dumper.cli']);
        $this->assertInstanceOf(Dumper\HtmlDumper::class, $app['dumper.html']);
        $this->assertInstanceOf(Cloner\VarCloner::class, $app['dumper.cloner']);
    }

    public function testProviderOverride()
    {
        $app = new Application(['debug' => true]);
        $provider = new DumperServiceProvider();
        $provider->register($app);

        $h = fopen('php://memory', 'r+b');
        $dumper = new Dumper\CliDumper($h);
        $dumper->setColors(false);
        $app['dumper.cli'] = $dumper;

        $app['dump'](['foo' => 'bar']);
        $data = stream_get_contents($h, -1, 0);
        fclose($h);

        $this->assertSame("array:1 [\n  \"foo\" => \"bar\"\n]\n", $data);
    }

    public function testProviderOverrideNoDebug()
    {
        $app = new Application(['debug' => false]);
        $provider = new DumperServiceProvider();
        $provider->register($app);

        $h = fopen('php://memory', 'r+b');
        $dumper = new Dumper\CliDumper($h);
        $dumper->setColors(false);
        $app['dumper.cli'] = $dumper;

        $app['dump'](['foo' => 'bar']);
        $data = stream_get_contents($h, -1, 0);
        fclose($h);

        $expected = '';
        $this->assertSame($expected, $data);
    }
}
