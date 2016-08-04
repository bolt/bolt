<?php
namespace Bolt\AccessControl;

use Bolt\Events\AccessControlEvent;
use Bolt\Events\AccessControlEvents;
use Bolt\Exception\AccessControlException;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManagerInterface;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Log\LoggerInterface;
use RandomLib\Generator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UAParser;

/**
 * Authentication handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessChecker
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;
    /** @var \Bolt\Logger\FlashLoggerInterface */
    protected $flashLogger;
    /** @var LoggerInterface */
    protected $systemLogger;
    /** @var \Bolt\AccessControl\Permissions */
    protected $permissions;
    /** @var \RandomLib\Generator */
    protected $randomGenerator;
    /** @var EventDispatcherInterface */
    protected $dispatcher;
    /** @var array */
    protected $cookieOptions;
    /** @var boolean */
    protected $validSession;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface   $em
     * @param RequestStack             $requestStack
     * @param SessionInterface         $session
     * @param EventDispatcherInterface $dispatcher
     * @param FlashLoggerInterface     $flashLogger
     * @param LoggerInterface          $systemLogger
     * @param Permissions              $permissions
     * @param Generator                $randomGenerator
     * @param array                    $cookieOptions
     */
    public function __construct(
        EntityManagerInterface $em,
        RequestStack $requestStack,
        SessionInterface $session,
        EventDispatcherInterface $dispatcher,
        FlashLoggerInterface $flashLogger,
        LoggerInterface $systemLogger,
        Permissions $permissions,
        Generator $randomGenerator,
        array $cookieOptions
    ) {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->dispatcher = $dispatcher;
        $this->flashLogger = $flashLogger;
        $this->systemLogger = $systemLogger;
        $this->permissions = $permissions;
        $this->randomGenerator = $randomGenerator;
        $this->cookieOptions = $cookieOptions;
    }

    /**
     * We will not allow tampering with sessions, so we make sure the current
     * session is still valid for the device on which it was created, and that
     * the username, and IP address, are still the same.
     *
     * 1. If user has a valid session and it is fresh, check against cookie:
     *    - If NOT a match refuse
     *    - If a match accept
     * 2. If user has a valid session and it is stale (>10 minutes), check the
     *    database records again:
     *    - If disabled refuse
     *    - If enabled
     *      - If NOT a match refuse
     *      - If a match accept
     *      - Update session data
     * 3. If user has no session check authtoken table entry (closed broswer):
     *    - If passed validity date refuse
     *    - If within validity date, hash username and IP against salt and
     *      compare to database:
     *      - If NOT a match refuse
     *      - If a match accept
     *
     * @param string $authCookie
     *
     * @throws AccessControlException
     *
     * @return boolean
     */
    public function isValidSession($authCookie)
    {
        if ($authCookie === null) {
            throw new AccessControlException('Can not validate session with an empty token.');
        }

        if ($this->validSession !== null) {
            return $this->validSession;
        }

        $check = false;
        $sessionAuth = null;

        /** @var \Bolt\AccessControl\Token\Token $sessionAuth */
        if ($this->session->isStarted() && $sessionAuth = $this->session->get('authentication')) {
            $check = $this->checkSessionStored($sessionAuth);
        }

        if (!$check) {
            // Either the session keys don't match, or the session is too old
            $check = $this->checkSessionDatabase($authCookie);
        }

        if ($check) {
            return $this->validSession = true;
        }
        $this->validSession = false;
        $this->systemLogger->debug("Clearing sessions for expired or invalid token: $authCookie", ['event' => 'authentication']);

        return $this->revokeSession();
    }

    /**
     * Log out the currently logged in user.
     *
     * @return boolean
     */
    public function revokeSession()
    {
        try {
            // Only show this flash if there are users in the system.
            // Not when we're about to get redirected to the "first users" screen.
            if ($this->getRepositoryUsers()->hasUsers()) {
                $this->flashLogger->info(Trans::__('general.phrase.access-denied-logged-out'));
            }
        } catch (TableNotFoundException $e) {
            // If we have no table, then we definitely have no users
        }

        // Remove all auth tokens when logging off a user
        if ($sessionAuth = $this->session->get('authentication')) {
            try {
                $this->getRepositoryAuthtoken()->deleteTokens($sessionAuth->getUser()->getUsername());
            } catch (TableNotFoundException $e) {
                // Database tables have been dropped
            }
        }

        $this->session->remove('authentication');
        $this->session->migrate(true);

        return false;
    }

    /**
     * Check the stored session, if we're past expiry then return false
     * regardless and force a check/update from the database authentication
     * record.
     *
     * @param Token\Token $sessionAuth
     *
     * @return boolean
     */
    protected function checkSessionStored(Token\Token $sessionAuth)
    {
        if (time() - $sessionAuth->getChecked() > 600) {
            return false;
        }

        return $this->checkSessionKeys($sessionAuth);
    }

    /**
     * Check the user authentication cookie against what is stored in the
     * database.
     *
     * @param string $authCookie
     *
     * @return boolean
     */
    protected function checkSessionDatabase($authCookie)
    {
        $userAgent = $this->cookieOptions['browseragent'] ? $this->getClientUserAgent() : null;

        try {
            if (!$authTokenEntity = $this->getRepositoryAuthtoken()->getToken($authCookie, $this->getClientIp(), $userAgent)) {
                return false;
            }

            if (!$databaseUser = $this->getRepositoryUsers()->getUser($authTokenEntity->getUsername())) {
                return false;
            }
        } catch (TableNotFoundException $e) {
            return false;
        }

        // Update session data
        $sessionAuth = new Token\Token($databaseUser, $authTokenEntity);
        $this->session->set('authentication', $sessionAuth);

        // Check if user is _still_ allowed to log on.
        if (!$this->permissions->isAllowed('login', $sessionAuth->getUser()->toArray(), null) || !$sessionAuth->isEnabled()) {
            $this->systemLogger->error('User ' . $sessionAuth->getUser()->getUsername() . ' has been disabled and can not login.', ['event' => 'authentication']);

            return false;
        }

        return $this->checkSessionKeys($sessionAuth);
    }

    /**
     * Check the session is still valid for the device on which it was created,
     * and. i.e. the username, IP address, and (if configured) the browser agent
     * values are all still the same.
     *
     * @param Token\Token $sessionAuth
     *
     * @return boolean
     */
    protected function checkSessionKeys(Token\Token $sessionAuth)
    {
        $userEntity = $sessionAuth->getUser();
        $tokenEntity = $sessionAuth->getToken();

        // The auth token is based on hostname, IP and browser user agent
        $key = $this->getAuthToken($userEntity->getUsername(), $tokenEntity->getSalt());

        if ($key === $tokenEntity->getToken()) {
            return true;
        }

        // Audit the failure
        $event = new AccessControlEvent($this->requestStack->getCurrentRequest());
        /** @var Token\Token $sessionAuth */
        $sessionAuth = $this->session->get('authentication');
        $userName = $sessionAuth ? $sessionAuth->getToken()->getUsername() : null;
        $event->setUserName($userName);
        $this->dispatcher->dispatch(AccessControlEvents::ACCESS_CHECK_FAILURE, $event->setReason(AccessControlEvents::FAILURE_INVALID));

        $this->systemLogger->error("Invalidating session: Recalculated session token '$key' doesn't match user provided token '" . $tokenEntity->getToken() . "'", ['event' => 'authentication']);
        $this->systemLogger->info("Automatically logged out user '" . $userEntity->getUsername() . "': Session data didn't match.", ['event' => 'authentication']);

        return false;
    }

    /**
     * Lookup active sessions.
     *
     * @return array
     */
    public function getActiveSessions()
    {
        // Parse the user-agents to get a user-friendly Browser, version and platform.
        $parser = UAParser\Parser::create();
        $this->getRepositoryAuthtoken()->deleteExpiredTokens();
        $sessions = $this->getRepositoryAuthtoken()->getActiveSessions() ?: [];

        foreach ($sessions as &$session) {
            $ua = $parser->parse($session->getUseragent());
            $session->setBrowser(sprintf('%s / %s', $ua->ua->toString(), $ua->os->toString()));
        }

        return $sessions;
    }

    /**
     * Get a MD5 hash key to identify the session with. This is calculated from
     * a name, a salt, and optionally the remote IP address, broswer's agent
     * string and the user's HTTP hostname.
     *
     * @param string $username
     * @param string $salt
     *
     * @return string|boolean
     */
    protected function getAuthToken($username, $salt)
    {
        if (empty($username) || empty($salt)) {
            throw new \InvalidArgumentException(__FUNCTION__ . ' required a name and salt to be provided.');
        }

        $token = (string) new Token\Generator($username, $salt, $this->getClientIp(), $this->getClientHost(), $this->getClientUserAgent(), $this->cookieOptions);

        $this->systemLogger->debug("Generating authentication cookie — Username: '$username' Salt: '$salt' IP: '{$this->getClientIp()}' Host name: '{$this->getClientHost()}' Agent: '{$this->getClientUserAgent()}' Result: $token", ['event' => 'authentication']);

        return $token;
    }

    /**
     * Return the user's host name.
     *
     * @return string
     */
    protected function getClientHost()
    {
        if ($this->requestStack->getCurrentRequest() === null) {
            throw new \RuntimeException(sprintf('%s can not be called outside of request cycle', __METHOD__));
        }

        return $this->requestStack->getCurrentRequest()->getHost();
    }

    /**
     * Return the user's IP address.
     *
     * @return string
     */
    protected function getClientIp()
    {
        if ($this->requestStack->getCurrentRequest() === null) {
            throw new \RuntimeException(sprintf('%s can not be called outside of request cycle', __METHOD__));
        }

        return $this->requestStack->getCurrentRequest()->getClientIp() ?: '127.0.0.1';
    }

    /**
     * Return the user's browser User Agent.
     *
     * @return string
     */
    protected function getClientUserAgent()
    {
        if ($this->requestStack->getCurrentRequest() === null) {
            throw new \RuntimeException(sprintf('%s can not be called outside of request cycle', __METHOD__));
        }

        return $this->requestStack->getCurrentRequest()->server->get('HTTP_USER_AGENT');
    }

    /**
     * @return Repository\UsersRepository
     */
    protected function getRepositoryUsers()
    {
        return $this->em->getRepository(Entity\Users::class);
    }

    /**
     * @return Repository\AuthtokenRepository
     */
    protected function getRepositoryAuthtoken()
    {
        return $this->em->getRepository(Entity\Authtoken::class);
    }
}
