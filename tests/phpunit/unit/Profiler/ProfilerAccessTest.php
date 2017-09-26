<?php

namespace Bolt\Tests\Profiler;

use Bolt\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Profiler access test.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ProfilerAccessTest extends TestCase
{
    public function providerNoAccessLoggedOut()
    {
        return [
            'Profiler base route' => [
                404, '/_profiler'
            ],
            'Profiler empty route' => [
                404, '/_profiler/empty'
            ],
            'Profiler latest route' => [
                404, '/_profiler/latest'
            ],
            'Profiler search route' => [
                404, '/_profiler/empty/search/results'
            ],
        ];
    }

    /**
     * @dataProvider providerNoAccessLoggedOut
     *
     * @param int    $expected
     * @param string $uri
     */
    public function testNoAccessLoggedOut($expected, $uri)
    {
        $app = Bootstrap::run(PHPUNIT_WEBROOT);
        $request = Request::create($uri, 'GET');
        $response = $app->handle($request);

        $this->assertEquals($expected, $response->getStatusCode());
    }
}
