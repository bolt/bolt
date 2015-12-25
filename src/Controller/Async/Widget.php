<?php
namespace Bolt\Controller\Async;

use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Async controller for widget async routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class Widget extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/widget/{key}', 'widget')
            ->bind('widget');
    }

    /**
     * Override of async request before method.
     *
     * NOTE: Routes on this controller *MUST* check their own authentication
     * where it is applicable.
     */
    public function before(Request $request)
    {
    }

    /**
     * Render a widget, and return the HTML, so it can be inserted in the page.
     *
     * @param string $key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function widget(Request $request, $key)
    {
        if (!$widget = $this->app['asset.queue.widget']->get($key)) {
            return $this->abort(Response::HTTP_NOT_FOUND);
        }

        $authCookie = $request->cookies->get($this->app['token.authentication.name']);
        if ($widget->getZone() !== 'frontend' && !$this->accessControl()->isValidSession($authCookie)) {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'You must be logged in to use this.');
        }

        $html = $this->app['asset.queue.widget']->getRendered($key);
        $response = new Response($html);
        $response->setSharedMaxAge(180)->setPublic();

        return $response;
    }
}
