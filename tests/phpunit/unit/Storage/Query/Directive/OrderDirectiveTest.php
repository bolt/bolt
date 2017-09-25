<?php

namespace Bolt\Tests\Storage\Query\Directive;

use Bolt\Storage\Query\Directive\OrderDirective;
use Bolt\Tests\Storage\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Storage\Query\Directive\OrderDirective
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class OrderDirectiveTest extends TestCase
{
    public function providerInvocation()
    {
        return [
            'Null parameter' => [
                'SELECT koala',
                null,
            ],
            'Empty string parameter' => [
                'SELECT koala',
                '',
            ],
            'False parameter' => [
                'SELECT koala',
                false,
            ],
            'Single field name parameter' => [
                'SELECT koala ORDER BY gum-leaves ASC',
                'gum-leaves',
            ],
            'Single field negated name parameter' => [
                'SELECT koala ORDER BY gum-leaves DESC',
                '-gum-leaves',
            ],
            'Multiple field name parameter' => [
                'SELECT koala ORDER BY gum-leaves ASC, eucalyptus ASC',
                'gum-leaves,eucalyptus',
            ],
            'Multiple field name negated parameters 1' => [
                'SELECT koala ORDER BY gum-leaves DESC, eucalyptus ASC',
                '-gum-leaves,eucalyptus',
            ],
            'Multiple field name negated parameters 2' => [
                'SELECT koala ORDER BY gum-leaves ASC, eucalyptus DESC',
                'gum-leaves,-eucalyptus',
            ],
            'Multiple field name negated parameters 3' => [
                'SELECT koala ORDER BY gum-leaves DESC, eucalyptus DESC',
                '-gum-leaves,-eucalyptus',
            ],
        ];
    }

    /**
     * @dataProvider providerInvocation
     *
     * @param string $expected
     * @param string $data
     */
    public function testInvocation($expected, $data)
    {
        $directive = new OrderDirective();
        $queryMock = new Mock\QueryInterfaceMock();
        $queryBuilder = $queryMock->getQueryBuilder();
        $queryBuilder->select('koala');

        $directive($queryMock, $data);

        $this->assertEquals($expected, $queryBuilder->getSQL());
    }
}
