<?php

namespace Bolt\Tests;

use Bolt\DeepDiff;

class DeepDiffTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider deepDiffProvider
     */
    public function testDeepDiff($a, $b, $expected)
    {
        $actual = DeepDiff::diff($a, $b);
        $this->assertEquals($expected, $actual);
    }

    public function deepDiffProvider()
    {
        return [
            [
                //No features at all
                [],
                [],
                [],
            ],
            [
                //Feature one stays the same
                ['feature one' => 'old feature'],
                ['feature one' => 'old feature'],
                [],
            ],
            [
                //Feature one gets removed
                ['feature one' => 'old feature'],
                [],
                [
                    ['feature one', 'old feature', null],
                ],
            ],
            [
                //feature one gets added
                [],
                ['feature one' => 'new feature'],
                [
                    ['feature one', null, 'new feature'],
                ],
            ],
            [
                //Feature one gets updated
                ['feature one' => 'old feature one'],
                ['feature one' => 'new feature one'],
                [
                    ['feature one', 'old feature one', 'new feature one'],
                ],
            ],
            [
                //multi feature one&two get updated
                [
                    'feature one' => 'old feature one',
                    'feature two' => 'old feature two',
                ],
                [
                    'feature one' => 'new feature one',
                    'feature two' => 'new feature two',
                ],
                [
                    ['feature one', 'old feature one', 'new feature one'],
                    ['feature two', 'old feature two', 'new feature two'],
                ],
            ],
        ];
    }
}
