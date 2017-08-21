<?php

namespace Bolt\Tests\Helper;

use Bolt\Helpers\Deprecated;
use PHPUnit\Framework\TestCase;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class DeprecatedTest extends TestCase
{
    protected $deprecations = [];

    public function testService()
    {
        Deprecated::service('foo', 3.4);
        $this->assertDeprecation('Accessing $app[\'foo\'] is deprecated since %d.%d and will be removed in %d.0.');
        Deprecated::service('foo', 3.4, 'bar');
        $this->assertDeprecation('Accessing $app[\'foo\'] is deprecated since %d.%d and will be removed in %d.0. Use $app[\'bar\'] instead.');
        Deprecated::service('foo', 3.4, 'Do it this way instead.');
        $this->assertDeprecation('Accessing $app[\'foo\'] is deprecated since %d.%d and will be removed in %d.0. Do it this way instead.');
    }

    protected function setUp()
    {
        $this->deprecations = [];
        set_error_handler(
            function ($type, $msg, $file, $line) {
                $this->deprecations[] = $msg;
            },
            E_USER_DEPRECATED
        );
    }

    protected function tearDown()
    {
        restore_error_handler();
    }

    private function assertDeprecation($msg)
    {
        $this->assertNotEmpty($this->deprecations, 'No deprecations triggered.');
        $this->assertStringMatchesFormat($msg, $this->deprecations[0]);
        $this->deprecations = [];
    }
}
