<?php
namespace Bolt\Controllers\Backend;

use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for authentication routes.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Authentication extends BackendBase
{
    public function addRoutes(ControllerCollection $c)
    {
        $c->get('/login', 'controllers.backend.authentication:actionGetLogin')
            ->bind('login');

        $c->post('/login', 'controllers.backend.authentication:actionPostLogin')
            ->bind('postLogin');

        $c->match('/logout', 'controllers.backend.authentication:actionLogout')
            ->bind('logout');

        $c->get('/resetpassword', 'controllers.backend.authentication:actionResetPassword')
            ->bind('resetpassword');
    }

    /**
     * Handle a login attempt.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionPostLogin(Request $request)
    {
        $username = trim($request->request->get('username'));
        $password = $request->request->get('password');
        switch ($request->get('action')) {
            case 'login':
                // Log in, if credentials are correct.
                $result = $this->getUsers()->login($username, $password);

                if ($result) {
                    $this->app['logger.system']->info('Logged in: ' . $username, array('event' => 'authentication'));
                    $retreat = $this->getSession()->get('retreat', array('route' => 'dashboard', 'params' => array()));
                    return $this->redirectToRoute($retreat['route'], $retreat['params']);
                }

                return $this->actionGetLogin($request);

            case 'reset':
                // Send a password request mail, if username exists.
                if (empty($username)) {
                    $this->addFlash('error', Trans::__('Please provide a username'));
                } else {
                    $this->getUsers()->resetPasswordRequest($username);
                    return $this->redirectToRoute('login');
                }

                return $this->actionGetLogin($request);
        }
        // Let's not disclose any internal information.
        $this->app->abort(Response::HTTP_BAD_REQUEST, 'Invalid request');
    }

    /**
     * Login page and "Forgotten password" page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionGetLogin(Request $request)
    {
        $user = $this->getUser();
        if (!empty($user) && $user['enabled'] == 1) {
            return $this->redirectToRoute('dashboard');
        }

        if ($this->getOption('general/enforce_ssl') && !$request->isSecure()) {
            return $this->redirect(preg_replace('/^http:/i', 'https:', $request->getUri()));
        }

        return $this->render('login/login.twig', array('randomquote' => true));
    }

    /**
     * Logout page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionLogout()
    {
        $user = $this->getSession()->get('user');
        $this->app['logger.system']->info('Logged out: ' . $user['displayname'], array('event' => 'authentication'));

        $this->getUsers()->logout();

        return $this->redirectToRoute('login');
    }

    /**
     * Reset the password. This controller is normally only reached when the user
     * clicks a "password reset" link in the email.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionResetPassword(Request $request)
    {
        $this->getUsers()->resetPasswordConfirm($request->get('token'));

        return $this->redirectToRoute('login');
    }
}
