<?php

namespace Bolt\Tests\Asset;

use Bolt\Tests\Fixtures\CallableInvokerTrait\CallableInvoker;
use Bolt\Tests\Fixtures\CallableInvokerTrait\Invokable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Asset\CallableInvokerTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CallableInvokerTraitTest extends TestCase
{
    public function providerCallbackIndexed()
    {
        return [
            ['koala-', null],
            ['koala-koala', ['koala']],
            ['koala-dropbear-koala', ['dropbear', 'koala']],
        ];
    }

    /**
     * @dataProvider providerCallbackIndexed
     *
     * @param string     $expect
     * @param array|null $args
     */
    public function testCallbackIndexed($expect, $args)
    {
        $invoker = new CallableInvoker();
        $result = $invoker->doInvokeCallable([$this, 'callMe'], $args);

        self::assertSame($expect, $result);
    }

    public function providerCallbackMapped()
    {
        return [
            ['found--', null],
            ['found-kenny-', ['koala' => 'kenny']],
            ['found-kenny-bruce', ['dropbear' => 'bruce', 'koala' => 'kenny']],
            ['found-kenny-bruce', ['koala' => 'kenny', 'dropbear' => 'bruce']],
        ];
    }

    /**
     * @dataProvider providerCallbackMapped
     *
     * @param string     $expect
     * @param array|null $args
     */
    public function testCallbackMapped($expect, $args)
    {
        $invoker = new CallableInvoker();
        $result = $invoker->doInvokeCallable([$this, 'findMe'], $args);

        self::assertSame($expect, $result);
    }

    /**
     * @dataProvider providerCallbackMapped
     *
     * @param string     $expect
     * @param array|null $args
     */
    public function testClosureMapped($expect, $args)
    {
        $func = function ($koala = null, $dropbear = null) {
            return 'found-' . $koala . '-' . $dropbear;
        };

        $invoker = new CallableInvoker();
        $result = $invoker->doInvokeCallable($func, $args);

        self::assertSame($expect, $result);
    }

    /**
     * @dataProvider providerCallbackMapped
     *
     * @param string     $expect
     * @param array|null $args
     */
    public function testInvokableMapped($expect, $args)
    {
        $func = new Invokable();

        $invoker = new CallableInvoker();
        $result = $invoker->doInvokeCallable($func, $args);

        self::assertSame($expect, $result);
    }

    public function callMe()
    {
        $args = func_get_args();

        return 'koala-' . implode('-', (array) $args);
    }

    public function findMe($koala = null, $dropbear = null)
    {
        return 'found-' . $koala . '-' . $dropbear;
    }
}
