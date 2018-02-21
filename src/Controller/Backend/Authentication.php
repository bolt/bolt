<?php

namespace Bolt\Controller\Backend;

use Bolt\AccessControl\Token\Token;
use Bolt\Common\Deprecated;
use Bolt\Events\AccessControlEvent;
use Bolt\Events\AccessControlEvents;
use Bolt\Form\FormType;
use Bolt\Response\TemplateResponse;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            ->bind('login')
            ->after(function (Request $request, Response $response) {
                $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();
            })
        ;

        $c->post('/login', 'getLogin')
            ->bind('postLogin');

        $c->match('/logout', 'logout')
            ->bind('logout');

        $c->get('/resetpassword', 'resetPassword')
            ->bind('resetpassword');
    }

    /**
     * Login page and "Forgotten password" page.
     *
     * @param Request $request
     *
     * @return TemplateResponse|RedirectResponse|Response
     */
    public function getLogin(Request $request)
    {
        $user = $this->getUser();
        if ($user && $user->getEnabled() == 1) {
            $response = $this->redirectToRoute('dashboard');

            $token = $this->session()->get('authentication');
            $this->setAuthenticationCookie($request, $response, $token);

            return $response;
        }

        if ($this->getOption('general/enforce_ssl') && !$request->isSecure()) {
            return $this->redirect(preg_replace('/^http:/i', 'https:', $request->getUri()));
        }

        $userEntity = new Entity\Users();
        // Generate the form
        $form = $this->createFormBuilder(FormType\UserLoginType::class, $userEntity)
            ->getForm()
            ->handleRequest($request)
        ;
        /** @var Form $form */
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->getClickedButton()->getName();
            if ($action === 'login') {
                $response = $this->handlePostLogin($request, $form);
            } elseif ($action === 'reset') {
                $response = $this->handlePostReset($request, $form);
            } else {
                // Let's not disclose any internal information.
                throw new HttpException(Response::HTTP_BAD_REQUEST, 'Invalid request');
            }
            if ($response instanceof Response) {
                return $response;
            }
        }
        $context = [
            'form'        => $form->createView(),
            'randomquote' => true,
        ];

        return $this->render('@bolt/login/login.twig', $context);
    }

    /**
     * Handle a login attempt.
     *
     * @param Request $request The Symfony Request
     *
     * @return Response|RedirectResponse
     */
    public function postLogin(Request $request)
    {
        Deprecated::method(3.4);

        return $this->getLogin($request);
    }

    /**
     * Logout page.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        $event = new AccessControlEvent($request);
        /** @var Token $sessionAuth */
        $sessionAuth = $this->session()->get('authentication');
        $userId = $sessionAuth ? $sessionAuth->getToken()->getUserId() : false;
        if ($userId && $sessionAuth->getUser()) {
            $userName = $sessionAuth->getUser()->getUsername();
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
            $request->getBasePath(),
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
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return Response
     */
    private function handlePostLogin(Request $request, FormInterface $form)
    {
        $event = new AccessControlEvent($request);
        $username = $form->get('username')->getData();
        $password = $form->get('password')->getData();

        if (!$this->login()->login($username, $password, $event)) {
            return null;
        }

        // Authentication data is cached in the session and if we can't get it
        // now, everyone is going to have a bad day. Make that obvious.
        if (!$token = $this->session()->get('authentication')) {
            $this->flashes()->error(Trans::__('general.phrase.error-session-data-login'));

            return null;
        }

        // Log in, if credentials are correct.
        $this->app['logger.system']->info('Logged in: ' . $username, ['event' => 'authentication']);

        $retreat = $this->session()->get('retreat', ['route' => 'dashboard', 'params' => []]);
        $response = $this->setAuthenticationCookie($request, $this->redirectToRoute($retreat['route'], $retreat['params']), (string) $token);

        return $response;
    }

    /**
     * Handle a password reset POST.
     *
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    private function handlePostReset(Request $request, FormInterface $form)
    {
        $event = new AccessControlEvent($request);
        $username = $form->get('username')->getData();

        // Send a password request mail, if username exists.
        if ($username === null || $username === '') {
            $this->flashes()->error(Trans::__('general.phrase.please-provide-username'));
        } else {
            $this->password()->resetPasswordRequest($username, $request->getClientIp(), $event);
            $response = $this->redirectToRoute('login');
            $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();

            return $response;
        }

        return null;
    }
}
