<?php
namespace Bolt\Controller\Backend;

use Bolt\Controller\Base;
use Bolt\Controller\Zone;
use Bolt\Events\AccessControlEvent;
use Bolt\Events\AccessControlEvents;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for all backend controllers.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class BackendBase extends Base
{
    public function connect(Application $app)
    {
        $c = parent::connect($app);
        $c->value(Zone::KEY, Zone::BACKEND);

        $c->before([$this, 'before']);

        return $c;
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, array $variables = [], array $globals = [])
    {
        if (!isset($variables['context'])) {
            $variables = ['context' => $variables];
        }

        return parent::render($template, $variables, $globals);
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request     $request   The Symfony Request
     * @param Application $app       The application/container
     * @param string      $roleRoute An overriding value for the route name in permission checks
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        $route = $request->get('_route');

        // Initial event
        $event = new AccessControlEvent($request);
        $app['dispatcher']->dispatch(AccessControlEvents::ACCESS_CHECK_REQUEST, $event);

        // Handle the case where the route doesn't equal the role.
        if ($roleRoute === null) {
            $roleRoute = $this->getRoutePermission($route);
        } else {
            $roleRoute = $this->getRoutePermission($roleRoute);
        }

        // Check for first user set up
        $response = $this->checkFirstUser($app, $route);
        if ($response !== true) {
            return $response;
        }

        // If we're resetting passwords, we have nothing more to check
        if ($route === 'resetpassword' || $route === 'login' || $route === 'postLogin' || $route === 'logout') {
            return null;
        }

        // Confirm the user is enabled or bounce them
        $sessionUser = $this->getUser();
        if ($sessionUser && !$sessionUser->getEnabled()) {
            $app['logger.flash']->error(Trans::__('general.phrase.login-account-disabled'));
            $event->setReason(AccessControlEvents::FAILURE_DISABLED);
            $event->setUserName($sessionUser->getUsername());
            $app['dispatcher']->dispatch(AccessControlEvents::ACCESS_CHECK_FAILURE, $event);

            return $this->redirectToRoute('logout');
        } elseif ($sessionUser) {
            $event->setUserName($sessionUser->getUsername());
        }

        // Check if there's at least one 'root' user, and otherwise promote the current user.
        $this->users()->checkForRoot();

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        $authCookie = $request->cookies->get($this->app['token.authentication.name']);
        if ($authCookie === null || !$this->accessControl()->isValidSession($authCookie)) {
            // Don't redirect on ajaxy requests (eg. when Saving a record), but send an error
            // message with a `500` status code instead.
            if ($request->isXmlHttpRequest()) {
                $response = ['error' => ['message' => Trans::__('general.phrase.redirect-detected')] ];

                return new JsonResponse($response, 500);
            }

            $app['logger.flash']->info(Trans::__('general.phrase.please-logon'));

            return $this->redirectToRoute('login');
        }

        if (!$this->isAllowed($roleRoute)) {
            $app['logger.flash']->error(Trans::__('general.phrase.access-denied-privilege-view-page'));
            $event->setReason(AccessControlEvents::FAILURE_DENIED);
            $app['dispatcher']->dispatch(AccessControlEvents::ACCESS_CHECK_FAILURE, $event);

            return $this->redirectToRoute('dashboard');
        }

        // Success!
        $app['dispatcher']->dispatch(AccessControlEvents::ACCESS_CHECK_SUCCESS, $event);

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

        return null;
    }

    /**
     * Temporary hack to get the permission name associated with the route.
     *
     * @internal
     *
     * @param string $route
     *
     * @return string
     */
    private function getRoutePermission($route)
    {
        if ($route === 'omnisearch-results') {
            return 'omnisearch';
        }

        return $route;
    }

    /**
     * Set the authentication cookie in the response.
     *
     * @param Response $response
     * @param string   $token
     *
     * @return Response
     */
    protected function setAuthenticationCookie(Response $response, $token)
    {
        $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();
        $response->headers->setCookie(
            new Cookie(
                $this->app['token.authentication.name'],
                $token,
                time() + $this->getOption('general/cookies_lifetime'),
                $this->resources()->getUrl('root'),
                $this->getOption('general/cookies_domain'),
                $this->getOption('general/enforce_ssl'),
                true
            )
        );

        return $response;
    }

    /**
     * Returns the Login object.
     *
     * @return \Bolt\AccessControl\Login
     */
    protected function login()
    {
        return $this->app['access_control.login'];
    }

    /**
     * Returns the Password object.
     *
     * @return \Bolt\AccessControl\Password
     */
    protected function password()
    {
        return $this->app['access_control.password'];
    }

    /**
     * Check and handle first user set up.
     *
     * @param Application $app
     * @param mixed       $route
     *
     * @return null|true|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkFirstUser(Application $app, $route)
    {
        // If we have a valid, logged in user, we're going to assume we can skip this (expensive) test.
        if ($app['users']->getCurrentUser() !== null) {
            return true;
        }

        // Check the database users table exists
        $tableExists = $app['schema']->hasUserTable();

        // Test if we have a valid users in our table
        $userCount = 0;
        if ($tableExists) {
            $userCount = $this->users()->hasUsers();
        }

        // If the users table is present, but there are no users, and we're on
        // /bolt/userfirst, we let the user stay, because they need to set up
        // the first user.
        if ($tableExists && $userCount === 0 && $route === 'userfirst') {
            return null;
        }

        // If there are no users in the users table, or the table doesn't exist.
        // Repair the DB, and let's add a new user.
        if (!$tableExists || $userCount === 0) {
            $app['schema']->update();
            $app['logger.flash']->info(Trans::__('general.phrase.users-none-create-first'));

            return $this->redirectToRoute('userfirst');
        }

        return true;
    }
}
