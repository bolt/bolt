<?php
namespace Bolt\Controller\Backend;

use Bolt\AccessControl\Token\Token;
use Bolt\Events\AccessControlEvent;
use Bolt\Events\AccessControlEvents;
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
     * @param boolean                                   $resetCookies
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getLogin(Request $request, $resetCookies = false)
    {
        $user = $this->getUser();
        if ($user && $user->getEnabled() == 1) {
            $response = $this->redirectToRoute('dashboard');

            $token = $this->session()->get('authentication');
            $this->setAuthenticationCookie($response, $token);

            return $response;
        }

        if ($this->getOption('general/enforce_ssl') && !$request->isSecure()) {
            return $this->redirect(preg_replace('/^http:/i', 'https:', $request->getUri()));
        }

        $response = $this->render('@bolt/login/login.twig', ['randomquote' => true]);
        $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();

        if ($resetCookies) {
            $response->headers->clearCookie($this->app['token.authentication.name']);
        }

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
        switch ($request->get('action')) {
            case 'login':
                return $this->handlePostLogin($request);

            case 'reset':
                return $this->handlePostReset($request);
        }
        // Let's not disclose any internal information.
        $this->abort(Response::HTTP_BAD_REQUEST, 'Invalid request');
    }

    /**
     * Logout page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function logout(Request $request)
    {
        $event = new AccessControlEvent($request);
        /** @var Token $sessionAuth */
        $sessionAuth = $this->session()->get('authentication');
        $userName = $sessionAuth ? $sessionAuth->getToken()->getUsername() : false;
        if ($userName) {
            $this->app['logger.system']->info('Logged out: ' . $userName, ['event' => 'authentication']);
            $event->setUserName($userName);
        }
        $this->app['dispatcher']->dispatch(AccessControlEvents::LOGOUT_SUCCESS, $event);

        // Clear the session
        $this->accessControl()->revokeSession();
        $this->session()->invalidate(-1);

        // Clear cookie data
        $response = $this->redirectToRoute('login');
        $response->headers->clearCookie(
            $this->app['token.authentication.name'],
            $this->resources()->getUrl('root'),
            $this->getOption('general/cookies_domain'),
            $this->getOption('general/enforce_ssl')
        );

        return $response;
    }

    /**
     * Reset the password. This route is normally only reached when the user
     * clicks a "password reset" link in the email.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        $event = new AccessControlEvent($request);
        $this->password()->resetPasswordConfirm($request->get('token'), $request->getClientIp(), $event);

        return $this->redirectToRoute('login', ['action' => 'login']);
    }

    /**
     * Handle a login POST.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    private function handlePostLogin(Request $request)
    {
        $event = new AccessControlEvent($request);
        $username = trim($request->request->get('username'));
        $password = $request->request->get('password');

        if (!$this->login()->login($username, $password, $event)) {
            return $this->getLogin($request, true);
        }

        // Authentication data is cached in the session and if we can't get it
        // now, everyone is going to have a bad day. Make that obvious.
        if (!$token = $this->session()->get('authentication')) {
            $this->flashes()->error(Trans::__('general.phrase.error-session-data-login'));

            return $this->getLogin($request);
        }

        // Log in, if credentials are correct.
        $this->app['logger.system']->info('Logged in: ' . $username, ['event' => 'authentication']);

        $retreat = $this->session()->get('retreat', ['route' => 'dashboard', 'params' => []]);
        $response = $this->setAuthenticationCookie($this->redirectToRoute($retreat['route'], $retreat['params']), (string) $token);

        return $response;
    }

    /**
     * Handle a password reset POST.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    private function handlePostReset(Request $request)
    {
        $event = new AccessControlEvent($request);
        $username = trim($request->request->get('username'));

        // Send a password request mail, if username exists.
        if ($username === null || $username === '') {
            $this->flashes()->error(Trans::__('general.phrase.please-provide-username'));
        } else {
            $this->password()->resetPasswordRequest($username, $request->getClientIp(), $event);
            $response = $this->redirectToRoute('login');
            $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();

            return $response;
        }

        return $this->getLogin($request);
    }
}
