<?php
namespace Bolt\Tests\Session;

use Bolt\Session\OptionsBag;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Session/OptionsBag.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class OptionsBagTest extends BoltUnitTest
{
    public function testInstanceOf()
    {
        $bag = new OptionsBag;
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\ParameterBag', $bag);
    }

    public function testGet()
    {
        $bag = new OptionsBag(['antistatic' => 'polyethylene terephthalate']);

        $this->assertObjectHasAttribute('parameters', $bag);
        $this->assertTrue($bag->has('antistatic'));
        $this->assertSame('polyethylene terephthalate', $bag->get('antistatic'));
    }

    public function testOffsetExists()
    {
        $bag = new OptionsBag(['antistatic' => 'polyethylene terephthalate']);

        $this->assertTrue($bag->offsetExists('antistatic'));
    }

    public function testOffsetSet()
    {
        $bag = new OptionsBag();
        $bag->offsetSet('antistatic', 'polyethylene terephthalate');

        $this->assertTrue($bag->offsetExists('antistatic'));
    }

    public function testOffsetUnset()
    {
        $bag = new OptionsBag(['antistatic' => 'polyethylene terephthalate']);
        $this->assertTrue($bag->has('antistatic'));

        $bag->offsetUnset('antistatic');
        $this->assertFalse($bag->has('antistatic'));
    }
}
