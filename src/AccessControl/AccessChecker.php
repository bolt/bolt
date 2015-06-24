<?php
namespace Bolt\AccessControl;

use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Repository\AuthtokenRepository;
use Bolt\Storage\Repository\UsersRepository;
use Bolt\Translation\Translator as Trans;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UAParser;

/**
 * Authentication handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessChecker
{
    /** @var \Bolt\Storage\Repository\AuthtokenRepository */
    protected $repositoryAuthtoken;
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    /** @var array */
    protected $cookieOptions;
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;
    /** @var \Bolt\Logger\FlashLogger */
    protected $flashLogger;
    /** @var LoggerInterface */
    protected $systemLogger;
    /** @var \Bolt\AccessControl\Permissions */
    protected $permissions;
    /** @var boolean */
    protected $validsession;
    /** @var string */
    protected $remoteIP;
    /** @var string */
    protected $userAgent;
    /** @var string */
    protected $hostName;

    /**
     * Constructor.
     *
     * @param AuthtokenRepository  $repositoryAuthtoken
     * @param UsersRepository      $repositoryUsers
     * @param SessionInterface     $session
     * @param FlashLoggerInterface $flashLogger
     * @param LoggerInterface      $systemLogger
     * @param Permissions          $permisisons
     * @param array                $cookieOptions
     */
    public function __construct(
        AuthtokenRepository $repositoryAuthtoken,
        UsersRepository $repositoryUsers,
        SessionInterface $session,
        FlashLoggerInterface $flashLogger,
        LoggerInterface $systemLogger,
        Permissions $permisisons,
        array $cookieOptions)
    {
        $this->repositoryAuthtoken = $repositoryAuthtoken;
        $this->repositoryUsers = $repositoryUsers;
        $this->session = $session;
        $this->flashLogger = $flashLogger;
        $this->systemLogger = $systemLogger;
        $this->permisisons = $permisisons;
        $this->cookieOptions = $cookieOptions;
    }

    /**
     * Set the request data.
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->hostName  = $request->getHost();
        $this->remoteIP  = $request->getClientIp() ?: '127.0.0.1';
        $this->userAgent = $request->server->get('HTTP_USER_AGENT');
    }

    /**
     * Check if a given token matches the current (correct) Anit-CSRF-like token.
     *
     * @param string $token
     *
     * @return boolean
     */
    public function checkAntiCSRFToken($token = '')
    {
        if ($token === $this->getAntiCSRFToken()) {
            return true;
        } else {
            $this->flashLogger->error('The security token was incorrect. Please try again.');

            return false;
        }
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
     *      - Update session data     *
     * 3. If user has no session check authtoken table entry (closed broswer):
     *    - If passed validity date refuse
     *    - If within validity date, hash username and IP against salt and
     *      compare to database:
     *      - If NOT a match refuse
     *      - If a match accept
     *
     * @param string $authCookie
     *
     * @return boolean
     */
    public function isValidSession($authCookie)
    {
        if ($this->validsession !== null) {
            return $this->validsession;
        }

        $check = false;
        $sessionAuth = null;

        /** @var \Bolt\AccessControl\Token $sessionAuth */
        if ($this->session->isStarted() && $sessionAuth = $this->session->get('authentication')) {
            $check = $this->checkSessionStored($sessionAuth);
        }

        if (!$check) {
            // Eithter the session keys don't match, or the session is too old
            $check = $this->checkSessionDatabase($authCookie);
        }

        if ($check) {
            return $this->validsession = true;
        }
        $this->validsession = false;

        return $this->revokeSession();
    }

    /**
     * Log out the currently logged in user.
     *
     * @return boolean
     */
    public function revokeSession()
    {
        $this->flashLogger->info(Trans::__('You have been logged out.'));

        // Remove all auth tokens when logging off a user
        if ($sessionAuth = $this->session->get('authentication')) {
            $this->repositoryAuthtoken->deleteTokens($sessionAuth->getUser()->getUsername());
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
     * @param Token $sessionAuth
     *
     * @return boolean
     */
    protected function checkSessionStored(Token $sessionAuth)
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
        if ($this->cookieOptions['browseragent']) {
            $userAgent = $this->userAgent;
        }

        if (!$authTokenEntity = $this->repositoryAuthtoken->getToken($authCookie, $this->remoteIP, $userAgent)) {
            return false;
        }

        if (!$databaseUser = $this->repositoryUsers->getUser($authTokenEntity->getUsername())) {
            return false;
        }

        // Update session data
        $sessionAuth = new Token($databaseUser, $authTokenEntity);
        $this->session->set('authentication', $sessionAuth);

        // Check if user is _still_ allowed to log on.
        if (!$this->permissions->isAllowed('login', $sessionAuth->getUser()->toArray(), null) || !$sessionAuth->isEnabled()) {
            return false;
        }

        return $this->checkSessionKeys($sessionAuth);
    }

    /**
     * Check the session is still valid for the device on which it was created,
     * and. i.e. the username, IP address, and (if configured) the browser agent
     * values are all still the same.
     *
     * @param Token $sessionAuth
     *
     * @return boolean
     */
    protected function checkSessionKeys(Token $sessionAuth)
    {
        $userEntity = $sessionAuth->getUser();
        $tokenEntity = $sessionAuth->getToken();

        // The auth token is based on hostname, IP and browser user agent
        $key = $this->getAuthToken($userEntity->getUsername(), $tokenEntity->getSalt());

        if ($key === $tokenEntity->getToken()) {
            return true;
        }

        $this->systemLogger->error("Keys don't match. Invalidating session: $key != " . $tokenEntity->getToken(), ['event' => 'authentication']);
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
        $this->repositoryAuthtoken->deleteExpiredTokens();
        $sessions = $this->repositoryAuthtoken->getActiveSessions() ?: [];

        foreach ($sessions as &$session) {
            $ua = $parser->parse($session->getUseragent());
            $session->setBrowser(sprintf('%s / %s', $ua->ua->toString(), $ua->os->toString()));
        }

        return $sessions;
    }

    /**
     * Generate a Anti-CSRF-like token, to use in GET requests for stuff that
     * ought to be POST-ed forms.
     *
     * @param string $token
     *
     * @return string
     */
    public function getAntiCSRFToken($token)
    {
        if ($this->cookieOptions['remoteaddr']) {
            $token .= '-' . $this->remoteIP;
        }
        if ($this->cookieOptions['browseragent']) {
            $token .= '-' . $this->userAgent;
        }
        if ($this->cookieOptions['httphost']) {
            $token .= '-' . $this->hostName;
        }

        return substr(md5($token), 0, 8);
    }

    /**
     * Get a key to identify the session with.
     *
     * @param string $name
     * @param string $salt
     *
     * @return string|boolean
     */
    private function getAuthToken($name = '', $salt = '')
    {
        if (empty($name)) {
            return false;
        }

        $seed = $name . '-' . $salt;

        if ($this->cookieOptions['remoteaddr']) {
            $seed .= '-' . $this->remoteIP;
        }
        if ($this->cookieOptions['browseragent']) {
            $seed .= '-' . $this->userAgent;
        }
        if ($this->cookieOptions['httphost']) {
            $seed .= '-' . $this->hostName;
        }

        $token = md5($seed);

        return $token;
    }

    /**
     * Calculate the amount of time until we should throttle login attempts for
     * a user.
     *
     * The amount is increased exponentially with each attempt: 1, 4, 9, 16, 25,
     * 36, .. seconds.
     *
     * Note: I just realized this is conceptually wrong: we should throttle
     * based on remote_addr, not username. So, this isn't used, yet.
     *
     * @param integer $attempts
     *
     * @return \DateTime
     */
    private function throttleUntil($attempts)
    {
        if ($attempts < 5) {
            return null;
        } else {
            $wait = pow(($attempts - 4), 2);

            $dt = new \DateTime();
            $di = new \DateInterval("PT{$wait}S");

            return $dt->add($di);
        }
    }
}
