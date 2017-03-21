<?php

namespace Bolt\Tests;

use Bolt\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testConstructorSetBoltVersion()
    {
        $app = new Application();
        $this->assertInstanceOf(\Silex\Application::class, $app);
    }
}
