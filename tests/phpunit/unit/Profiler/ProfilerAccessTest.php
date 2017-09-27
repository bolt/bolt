<?php

namespace Bolt\Tests\Profiler;

use Bolt\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

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
            'Home route' => [
                404, '_profiler_home', [],
            ],
            'Base route' => [
                404, '_profiler', ['token' => 'dropbear'],
            ],
            'Search page' => [
                404, '_profiler_search', [],
            ],
            'Search bar' => [
                404, '_profiler_search_bar', [],
            ],
            'Search results' => [
                404, '_profiler_search_results', ['token' => 'dropbear'],
            ],
            'phpinfo()' => [
                404, '_profiler_phpinfo', [],
            ],
            'About page' => [
                404, '_profiler_info', ['about' => 'koala'],
            ],
            'Purge' => [
                404, '_profiler_purge', [],
            ],
            'Router' => [
                404, '_profiler_router', ['token' => 'dropbear'],
            ],
            'Exception' => [
                404, '_profiler_exception', ['token' => 'dropbear'],
            ],
            'Exception CSS' => [
                404, '_profiler_exception_css', ['token' => 'dropbear'],
            ],
            'Web debug toolbar' => [
                404, '_wdt', ['token' => 'dropbear'],
            ],
        ];
    }

    /**
     * @dataProvider providerNoAccessLoggedOut
     *
     * @param int    $expected
     * @param string $bind
     * @param array  $parameters
     */
    public function testNoAccessLoggedOut($expected, $bind, array $parameters)
    {
        $app = Bootstrap::run(PHPUNIT_WEBROOT);
        $app['twig'] = new Environment(new ArrayLoader(['notfound.twig' => 'No koala']), ['strict_variables' => false]);
        $app->boot();

        $generator = $app['url_generator'];
        $uri = $generator->generate($bind, $parameters);

        $request = Request::create($uri, 'GET');
        $response = $app->handle($request);

        $this->assertEquals($expected, $response->getStatusCode());
    }
}
