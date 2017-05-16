<?php

namespace Bolt\Tests\Security\Random;

use Bolt\Security\Random\Generator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Security\Random\Generator
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new Generator();

        foreach ([8, 16, 32, 128] as $length) {
            $result = $generator->generate($length);

            $this->assertSame($length, strlen($result));
        }
    }

    public function testGenerateInt()
    {
        $generator = new Generator();

        foreach ([8 => 12, 16 => 22, 32 => 64, 128 => 256] as $min => $max) {
            $result = $generator->generateInt($min, $max);
            $this->assertTrue($result >= $min);
            $this->assertTrue($result <= $max);
        }
    }

    public function testGenerateString()
    {
        $generator = new Generator();

        foreach ([8, 16, 32, 128] as $length) {
            $result = $generator->generateString($length);

            $this->assertSame($length, strlen($result));
            $this->assertRegExp('/^[a-fA-F0-9]+$/', $result);
        }
    }
}
