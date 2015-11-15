<?php
namespace Bolt\Controller\Async;

use Bolt\Controller\Base;
use Bolt\Controller\Zone;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for all async controllers.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class AsyncBase extends Base
{
    public function connect(Application $app)
    {
        $c = parent::connect($app);
        $c->value(Zone::KEY, Zone::ASYNC);

        $c->before([$this, 'before']);

        return $c;
    }

    /**
     * Middleware function to do some tasks that should be done for all
     * asynchronous requests.
     */
    public function before(Request $request)
    {
        // Start the 'stopwatch' for the profiler.
        $this->app['stopwatch']->start('bolt.async.before');

        // If there's no active session, don't do anything.
        $authCookie = $request->cookies->get($this->app['token.authentication.name']);
        if ($authCookie === null || !$this->accessControl()->isValidSession($authCookie)) {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'You must be logged in to use this.');
        }

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.async.before');
    }
}
