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
        return array(
            array(
                //No features at all
                array(),
                array(),
                array()
            ),
            array(
                //Feature one stays the same
                array('feature one' => 'old feature'),
                array('feature one' => 'old feature'),
                array()
            ),
            array(
                //Feature one gets removed
                array('feature one' => 'old feature'),
                array(),
                array(
                    array('feature one', 'old feature', null)
                )
            ),
            array(
                //feature one gets added
                array(),
                array('feature one' => 'new feature'),
                array(
                    array('feature one', null, 'new feature')
                )
            ),
            array(
                //Feature one gets updated
                array('feature one' => 'old feature one'),
                array('feature one' => 'new feature one'),
                array(
                    array('feature one', 'old feature one', 'new feature one')
                )
            ),
            array(
                //multi feature one&two get updated
                array(
                    'feature one' => 'old feature one',
                    'feature two' => 'old feature two'
                ),
                array(
                    'feature one' => 'new feature one',
                    'feature two' => 'new feature two'
                ),
                array(
                    array('feature one', 'old feature one', 'new feature one'),
                    array('feature two', 'old feature two', 'new feature two')
                )
            ),
        );
    }
}