<?php
namespace Bolt\AccessControl;

use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Repository\AuthtokenRepository;
use Bolt\Storage\Repository\UsersRepository;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Log\LoggerInterface;
use RandomLib\Generator;
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
    /** @var \Bolt\Storage\Repository\UsersRepository */
    protected $repositoryUsers;
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
    /** @var array */
    protected $cookieOptions;
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
     * @param Permissions          $permissions
     * @param Generator            $randomGenerator
     * @param array                $cookieOptions
     */
    public function __construct(
        AuthtokenRepository $repositoryAuthtoken,
        UsersRepository $repositoryUsers,
        SessionInterface $session,
        FlashLoggerInterface $flashLogger,
        LoggerInterface $systemLogger,
        Permissions $permissions,
        Generator $randomGenerator,
        array $cookieOptions
    ) {
        $this->repositoryAuthtoken = $repositoryAuthtoken;
        $this->repositoryUsers = $repositoryUsers;
        $this->session = $session;
        $this->flashLogger = $flashLogger;
        $this->systemLogger = $systemLogger;
        $this->permissions = $permissions;
        $this->randomGenerator = $randomGenerator;
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
     * @return boolean
     */
    public function isValidSession($authCookie)
    {
        if ($this->validsession !== null) {
            return $this->validsession;
        }

        $check = false;
        $sessionAuth = null;

        /** @var \Bolt\AccessControl\Token\Token $sessionAuth */
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
        $userAgent = $this->cookieOptions['browseragent'] ? $this->userAgent : null;

        try {
            if (!$authTokenEntity = $this->repositoryAuthtoken->getToken($authCookie, $this->remoteIP, $userAgent)) {
                return false;
            }

            if (!$databaseUser = $this->repositoryUsers->getUser($authTokenEntity->getUsername())) {
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
            $this->systemLogger->error('User ' . $sessionAuth->getUser()->getUserName() . ' has been disabled and can not login.', ['event' => 'authentication']);

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
        $this->repositoryAuthtoken->deleteExpiredTokens();
        $sessions = $this->repositoryAuthtoken->getActiveSessions() ?: [];

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

        $token = (string) new Token\Generator($username, $salt, $this->remoteIP, $this->hostName, $this->userAgent, $this->cookieOptions);

        $this->systemLogger->debug("Generating authentication cookie — Username: '$username' Salt: '$salt' IP: '{$this->remoteIP}' Host name: '{$this->hostName}' Agent: '{$this->userAgent}' Result: $token", ['event' => 'authentication']);

        return $token;
    }
}
