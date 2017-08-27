<?php

namespace Bolt\Tests\Helper;

use Bolt\Collection\Bag;
use Bolt\Helpers\RequestSanitiser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @covers \Bolt\Helpers\RequestSanitiser
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RequestSanitiserTest extends TestCase
{
    public function testFilter()
    {
        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $result = RequestSanitiser::filter($request);

        $this->assertInstanceOf(Bag::class, $result);
        $this->assertSame(
            ['attributes', 'query', 'files', 'cookies', 'headers', 'server', 'session'],
            $result->keys()->toArray()
        );
    }
}
