<?php
namespace Bolt\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $c->get('/login', 'getLogin')
            ->bind('login');

        $c->post('/login', 'postLogin')
            ->bind('postLogin');

        $c->match('/logout', 'logout')
            ->bind('logout');

        $c->get('/resetpassword', 'resetPassword')
            ->bind('resetpassword');
    }

    /**
     * Login page and "Forgotten password" page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getLogin(Request $request)
    {
        $user = $this->getUser();
        if (!empty($user) && $user['enabled'] == 1) {
            return $this->redirectToRoute('dashboard');
        }

        if ($this->getOption('general/enforce_ssl') && !$request->isSecure()) {
            return $this->redirect(preg_replace('/^http:/i', 'https:', $request->getUri()));
        }

        $response = $this->render('login/login.twig', ['randomquote' => true]);
        $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();

        return $response;
    }

    /**
     * Handle a login attempt.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postLogin(Request $request)
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
    public function logout()
    {
        $user = $this->session()->get('user');
        $this->app['logger.system']->info('Logged out: ' . $user['displayname'], ['event' => 'authentication']);

        $this->authentication()->logout();

        $response = $this->redirectToRoute('login');
        $response->headers->clearCookie($this->app['token.authentication.name']);

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
    public function resetPassword(Request $request)
    {
        $this->authentication()->resetPasswordConfirm($request->get('token'));

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
        $token = $this->authentication()->login($username, $password);

        if ($token === false) {
            return $this->getLogin($request);
        }

        // Log in, if credentials are correct.
        $this->app['logger.system']->info('Logged in: ' . $username, ['event' => 'authentication']);
        $retreat = $this->session()->get('retreat', ['route' => 'dashboard', 'params' => []]);
        $response = $this->setAuthenticationCookie($this->redirectToRoute($retreat['route'], $retreat['params']), $token);

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
            $this->flashes()->error(Trans::__('Please provide a username'));
        } else {
            $this->authentication()->resetPasswordRequest($username);
            $response = $this->redirectToRoute('login');
            $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();

            return $response;
        }

        return $this->getLogin($request);
    }
}
