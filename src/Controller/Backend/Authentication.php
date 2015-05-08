<?php
namespace Bolt\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Cookie;
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
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/login', 'actionGetLogin')
            ->bind('login');

        $c->post('/login', 'actionPostLogin')
            ->bind('postLogin');

        $c->match('/logout', 'actionLogout')
            ->bind('logout');

        $c->get('/resetpassword', 'actionResetPassword')
            ->bind('resetpassword');
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
                return $this->handlePostLogin($request, $username, $password);

            case 'reset':
                return $this->handlePostReset($request, $username);
        }
        // Let's not disclose any internal information.
        $this->abort(Response::HTTP_BAD_REQUEST, 'Invalid request');
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

        $this->getAuthentication()->logout();

        $response = $this->redirectToRoute('login');
        $response->headers->clearCookie('bolt_authtoken');

        return $response;
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
        $this->getAuthentication()->resetPasswordConfirm($request->get('token'));

        return $this->redirectToRoute('login');
    }

    /**
     * Handle a login POST.
     *
     * @param Request $request
     * @param string  $username
     * @param string  $password
     *
     * @return RedirectResponse
     */
    private function handlePostLogin(Request $request, $username, $password)
    {
        $token = $this->getAuthentication()->login($username, $password);

        if ($token === false) {
            return $this->actionGetLogin($request);
        }

        // Log in, if credentials are correct.
        $this->app['logger.system']->info('Logged in: ' . $username, array('event' => 'authentication'));
        $retreat = $this->getSession()->get('retreat', array('route' => 'dashboard', 'params' => array()));
        $response = $this->redirectToRoute($retreat['route'], $retreat['params']);
        $response->headers->setCookie(new Cookie(
            'bolt_authtoken',
            $token,
            time() + $this->getOption('general/cookies_lifetime'),
            '/',
            $this->getOption('general/cookies_domain'),
            $this->getOption('general/enforce_ssl'),
            true
        ));

        return $response;
    }

    /**
     * Handle a password reset POST.
     *
     * @param Request $request
     * @param string  $username
     *
     * @return RedirectResponse
     */
    private function handlePostReset(Request $request, $username)
    {
        // Send a password request mail, if username exists.
        if (empty($username)) {
            $this->addFlash('error', Trans::__('Please provide a username'));
        } else {
            $this->getAuthentication()->resetPasswordRequest($username);
            return $this->redirectToRoute('login');
        }

        return $this->actionGetLogin($request);
    }
}
