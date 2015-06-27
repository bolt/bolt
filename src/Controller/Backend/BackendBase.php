<?php
namespace Bolt\Controller\Backend;

use Bolt\Controller\Base;
use Bolt\Controller\Zone;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Symfony\Component\HttpFoundation\Cookie;
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
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        $route = $request->get('_route');

        // Handle the case where the route doesn't equal the role.
        if ($roleRoute === null) {
            $roleRoute = $route;
        }

        // Sanity checks for doubles in in contenttypes. This has to be done
        // here, because the 'translator' classes need to be initialised.
        $app['config']->checkConfig();

        // If we had to reload the config earlier on because we detected a
        // version change, display a notice.
        if ($app['config']->notify_update) {
            $notice = Trans::__("Detected Bolt version change to <b>%VERSION%</b>, and the cache has been cleared. Please <a href=\"%URI%\">check the database</a>, if you haven't done so already.",
                ['%VERSION%' => $app->getVersion(), '%URI%' => $app['resources']->getUrl('bolt') . 'dbcheck']);
            $app['logger.system']->notice(strip_tags($notice), ['event' => 'config']);
            $app['logger.flash']->info($notice);
        }

        // Check the database users table exists
        $tableExists = $app['schema']->checkUserTableIntegrity();

        // Test if we have a valid users in our table
        $hasUsers = false;
        if ($tableExists) {
            $hasUsers = $this->users()->hasUsers();
        }

        // If the users table is present, but there are no users, and we're on
        // /bolt/userfirst, we let the user stay, because they need to set up
        // the first user.
        if ($tableExists && !$hasUsers && $route === 'userfirst') {
            return null;
        }

        // If there are no users in the users table, or the table doesn't exist.
        // Repair the DB, and let's add a new user.
        if (!$tableExists || !$hasUsers) {
            $app['schema']->repairTables();
            $app['logger.flash']->info(Trans::__('There are no users in the database. Please create the first user.'));

            return $this->redirectToRoute('userfirst');
        }

        // Confirm the user is enabled or bounce them
        if (($sessionUser = $this->getUser()) && !$sessionUser->getEnabled() && $route !== 'userfirst' && $route !== 'login' && $route !== 'postLogin' && $route !== 'logout') {
            $app['logger.flash']->error(Trans::__('Your account is disabled. Sorry about that.'));

            return $this->redirectToRoute('logout');
        }

        // Check if there's at least one 'root' user, and otherwise promote the current user.
        $this->users()->checkForRoot();

        // If we're resetting passwords, we have nothing more to check
        if ($route === 'resetpassword') {
            return null;
        }

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        $authCookie = $request->cookies->get($this->app['token.authentication.name']);
        if (!$this->authentication()->isValidSession($authCookie) && !$this->isAllowed($route)) {
            $app['logger.flash']->info(Trans::__('Please log on.'));

            return $this->redirectToRoute('login');
        } elseif (!$this->isAllowed($roleRoute)) {
            $app['logger.flash']->error(Trans::__('You do not have the right privileges to view that page.'));

            return $this->redirectToRoute('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

        return null;
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
        $response->headers->setCookie(new Cookie(
            $this->app['token.authentication.name'],
            $token,
            time() + $this->getOption('general/cookies_lifetime'),
            $this->resources()->getUrl('root'),
            $this->getOption('general/cookies_domain'),
            $this->getOption('general/enforce_ssl'),
            true
        ));

        return $response;
    }

    /**
     * Returns the Login object.
     *
     * @return \Bolt\AccessControl\Login
     */
    protected function login()
    {
        return $this->app['authentication.login'];
    }

    /**
     * Returns the Password object.
     *
     * @return \Bolt\AccessControl\Password
     */
    protected function password()
    {
        return $this->app['authentication.password'];
    }
}
