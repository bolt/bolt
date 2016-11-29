<?php

namespace Bolt\Tests\Canonical;

use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for Canonical class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class CanonicalTest extends BoltUnitTest
{
    public function provider()
    {
        return [
            'default'                  => [null, '/drop/bear', 'http://bolt.dev/drop/bear'],
            'override host'            => ['koala.org.au', '/drop/bear', 'http://koala.org.au/drop/bear'],
            'override host and https'  => ['https://koala.org.au', '/drop/bear', 'https://koala.org.au/drop/bear'],
            'https does not downgrade' => ['koala.org.au', 'https://koala.org.au/drop/bear', 'https://koala.org.au/drop/bear'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testCanonical($override, $uri, $expected)
    {
        $app = $this->getApp(false);
        $app->match('/drop/bear', function () { return '<html><head></head><body>Koala!</body></html>'; });
        if ($override !== null) {
            $app['config']->set('general/canonical', $override);
        }
        $request = Request::create($uri);

        $url = null;
        $app->after(function() use (&$url, $app) {
            $url = $app['canonical']->getUrl();
        });

        $app->handle($request);

        $this->assertEquals($expected, $url);
    }
}
