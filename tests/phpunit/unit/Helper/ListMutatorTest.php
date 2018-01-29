<?php

namespace Bolt\Tests\Helper;

use Bolt\Helpers\ListMutator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Helpers\ListMutator
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ListMutatorTest extends TestCase
{
    public static $available = [
        'Salad'  => 'salad',
        'Sushi'  => 'sushi',
        'Ham'    => 'ham',
        'Egg'    => 'egg',
        'Bread'  => 'bread',
        'Spring' => 'spring',
        'Pizza'  => 'pizza',
    ];

    public function provider()
    {
        yield 'All values mutatable, original & proposed are the same' => [
            self::$available, ['ham', 'egg'], ['ham', 'egg'], ['ham', 'egg'],
        ];
        yield 'All values mutatable, and an invalid value in original' => [
            self::$available, ['ham', 'egg', 'koala'], ['ham', 'salad'], ['ham', 'salad'],
        ];
        yield 'Immutable value can not be removed from the original' => [
            [], ['ham', 'egg'], ['egg'], ['ham', 'egg'],
        ];
        yield 'Immutable value can not be added' => [
            [], ['ham'], ['egg'], ['ham'],
        ];
        yield 'Nothing is mutatable, and not values are proposed' => [
            [], self::$available, [], array_values(self::$available),
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testMutationResults(array $mutable, array $original, array $proposed, array $expected)
    {
        $mutator = new ListMutator(self::$available, $mutable);

        self::assertSame($expected, $mutator($original, $proposed));
    }
}
