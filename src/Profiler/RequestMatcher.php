<?php

namespace Bolt\Profiler;

use Bolt\AccessControl\AccessChecker;
use Bolt\Config;
use Bolt\Request\ProfilerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Profiler conditional request matching enable/disable.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RequestMatcher implements RequestMatcherInterface
{
    use ProfilerAwareTrait;

    /** @var Config */
    private $config;
    /** @var SessionInterface */
    private $session;
    /** @var AccessChecker */
    private $acl;

    /**
     * Constructor.
     *
     * @param Config           $config
     * @param SessionInterface $session
     * @param AccessChecker    $acl
     */
    public function __construct(Config $config, SessionInterface $session, AccessChecker $acl)
    {
        $this->config = $config;
        $this->session = $session;
        $this->acl = $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Request $request)
    {
        if ($this->isProfilerRequest($request)) {
            false;
        }
        $authCookie = $request->cookies->get($request->attributes->get('_auththoken_name'));
        $isValidSession = $authCookie && $this->session->isStarted() && $this->acl->isValidSession($authCookie);
        $wantsDebug = $request->query->getBoolean('debug');
        $isDebug = $this->config->get('general/debug');
        $showLoggedOff = $this->config->get('general/debug_show_loggedoff');

        if (($isDebug || $wantsDebug) && ($isValidSession || $showLoggedOff)) {
            return true;
        }

        return false;
    }
}

