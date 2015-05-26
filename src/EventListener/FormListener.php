<?php
namespace Bolt\EventListener;

use Bolt\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Symfony Forms listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FormListener implements EventSubscriberInterface
{
    /** @var SessionInterface $session */
    protected $session;
    /** @var \Bolt\Config $config */
    protected $config;
    /** @var RequestStack $requestStack */
    protected $requestStack;
    /** @var NativeFileSessionHandler $handler */
    protected $handler;
    /** @var string $tokenName */
    protected $tokenName;

    /**
     * Constructor function.
     *
     * @param Session                       $session
     * @param RequestStack                  $request
     * @param Config                        $config
     * @param NativeFileSessionHandler|null $handler
     * @param string                        $tokenName
     */
    public function __construct(SessionInterface $session, RequestStack $requestStack, Config $config, $handler, $tokenName)
    {
        $this->session      = $session;
        $this->requestStack = $requestStack;
        $this->config       = $config;
        $this->handler      = $handler;
        $this->tokenName    = $tokenName;
    }

    /**
     * Handle the form event at the beginning of the Form::setData() method.
     *
     * @param FormEvent $event
     */
    public function onFormPreSetData(FormEvent $event)
    {
        if ($this->session->isStarted() || $this->requestStack->getCurrentRequest() === null) {
            return;
        }

        // Enable route specific sesion cookies, generally speaking for front end
        $storage = new NativeSessionStorage(
            [
                'name'            => $this->tokenName,
                'cookie_path'     => $this->requestStack->getCurrentRequest()->getRequestUri(),
                'cookie_domain'   => $this->config->get('general/cookies_domain'),
                'cookie_secure'   => $this->config->get('general/enforce_ssl'),
                'cookie_httponly' => true,
            ],
            $this->handler
        );

        $this->session = new Session($storage);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => ['onFormPreSetData', 10000],
        );
    }
}
