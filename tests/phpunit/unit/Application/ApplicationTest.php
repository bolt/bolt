<?php

namespace Bolt\Tests;

use Bolt\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testConstructorSetBoltVersion()
    {
        $app = new Application();

        $this->assertArrayHasKey('bolt_version', $app);
        $this->assertArrayHasKey('bolt_name', $app);
        $this->assertArrayHasKey('bolt_released', $app);
        $this->assertArrayHasKey('bolt_long_version', $app);
    }
}
