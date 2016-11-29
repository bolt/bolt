<?php

namespace Bolt\Tests\Canonical;

use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for Canonical class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CanonicalTest extends BoltUnitTest
{
    public function testCanonical()
    {
        $app = $this->getApp(false);
        $app->match('/drop/bear', function () { return '<html><head></head><body>Koala!</body></html>'; });
        $canonical = $app['config']->get('general/canonical');
        $app['config']->set('general/canonical', 'koala.org.au');
        $request = Request::create('https://koala.org.au/drop/bear');

        $url = null;
        $app->after(function() use (&$url, $app) {
            $url = $app['canonical']->getUrl();
        });

        $app->handle($request);
        $app['config']->set('general/canonical', $canonical);

        $this->assertEquals('https://koala.org.au/drop/bear', $url);
    }
}
