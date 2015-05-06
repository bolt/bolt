<?php
namespace Bolt\Routing;

use Bolt\AccessControl\Authentication;
use Bolt\Users;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectListener implements EventSubscriberInterface
{
    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;
    /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var \Bolt\Users */
    protected $users;
    /** @var \Bolt\Authentication $authentication */
    protected $authentication;

    /**
     * RedirectListener constructor.
     *
     * @param \Symfony\Component\HttpFoundation\Session\Session          $session
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
     * @param \Bolt\Users                                                $users
     */
    public function __construct(Session $session, UrlGeneratorInterface $urlGenerator, Users $users, Authentication $authentication)
    {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->users = $users;
        $this->authentication = $authentication;
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        if (!$response->isRedirect() || !$response instanceof RedirectResponse) {
            return;
        }

        $this->handleNoBackendAccess($response);
        $this->handleLoginRetreat($request, $response);
    }

    /**
     * When redirecting to the backend dashboard (while logged in),
     * if the user does not have access change the redirect to the homepage.
     *
     * @param \Symfony\Component\HttpFoundation\RedirectResponse $response
     */
    protected function handleNoBackendAccess(RedirectResponse $response)
    {
        if (!$this->authentication->isValidSession()) {
            return;
        }

        $dashboardPath = $this->urlGenerator->generate('dashboard');
        $dashboardAccess = $this->users->isAllowed('dashboard');
        if ($response->getTargetUrl() === $dashboardPath && !$dashboardAccess) {
            $this->session->getFlashBag()->clear();
            $response->setTargetUrl($this->urlGenerator->generate('homepage'));
        }
    }

    /**
     * When redirecting to login page set the 'retreat' variable in the session.
     * This allows a redirect back to the current page after successful login.
     *
     * @param \Symfony\Component\HttpFoundation\Request          $request
     * @param \Symfony\Component\HttpFoundation\RedirectResponse $response
     */
    protected function handleLoginRetreat(Request $request, RedirectResponse $response)
    {
        $route = $request->attributes->get('_route');

        if ($response->getTargetUrl() === $this->urlGenerator->generate('login') && $route !== 'logout') {
            $this->session->set('retreat', array(
                'route'  => $route,
                'params' => $request->attributes->get('_route_params'),
            ));
        } else {
            $this->session->remove('retreat');
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onResponse',
        );
    }
}
