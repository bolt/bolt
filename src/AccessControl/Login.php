<?php
namespace Bolt\AccessControl;

use Bolt\AccessControl\Token\Token;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Carbon\Carbon;
use Hautelook\Phpass\PasswordHash;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Login authentication handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Login extends AccessChecker
{
    /** @var \Silex\Application $app */
    protected $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
        $repoUsers = $app['storage']->getRepository('Bolt\Storage\Entity\Users');

        parent::__construct(
            $repoAuth,
            $repoUsers,
            $app['session'],
            $app['logger.flash'],
            $app['logger.system'],
            $app['permissions'],
            $app['randomgenerator'],
            $app['access_control.cookie.options']
        );

        $this->app = $app;
    }

    /**
     * Attempt to login a user with the given password. Accepts username or
     * email.
     *
     * @param Request $request
     * @param string  $userName
     * @param string  $password
     *
     * @return boolean
     */
    public function login(Request $request, $userName = null, $password = null)
    {
        $this->setRequest($request);
        $authCookie = $request->cookies->get($this->app['token.authentication.name']);

        // Remove expired tokens
        $this->repositoryAuthtoken->deleteExpiredTokens();

        if ($userName !== null && $password !== null) {
            return $this->loginCheckPassword($userName, $password);
        } elseif ($authCookie !== null) {
            return $this->loginCheckAuthtoken($authCookie);
        }

        $this->flashLogger->error(Trans::__('Invalid login parameters.'));

        return false;
    }

    /**
     * Check a user login request for username/password combinations.
     *
     * @param string $userName
     * @param string $password
     *
     * @return boolean
     */
    protected function loginCheckPassword($userName, $password)
    {
        if (!$userEntity = $this->getUserEntity($userName)) {
            return false;
        }

        $hasher = new PasswordHash($this->app['access_control.hash.strength'], true);
        if (!$hasher->CheckPassword($password, $userEntity->getPassword())) {
            $this->loginFailed($userEntity);

            return false;
        }

        return $this->loginFinish($userEntity);
    }

    /**
     * Attempt to login a user via the bolt_authtoken cookie.
     *
     * @param string $authCookie
     *
     * @return boolean
     */
    protected function loginCheckAuthtoken($authCookie)
    {
        if (!$userTokenEntity = $this->repositoryAuthtoken->getToken($authCookie, $this->remoteIP, $this->userAgent)) {
            $this->flashLogger->error(Trans::__('Invalid login parameters.'));

            return false;
        }

        $checksalt = $this->getAuthToken($userTokenEntity->getUsername(), $userTokenEntity->getSalt());
        if ($checksalt === $userTokenEntity->getToken()) {
            if (!$userEntity = $this->getUserEntity($userTokenEntity->getUsername())) {
                return false;
            }

            $userTokenEntity->setLastseen(Carbon::now());
            $userTokenEntity->setValidity(Carbon::create()->addSeconds($this->cookieOptions['lifetime']));
            $this->repositoryAuthtoken->save($userTokenEntity);
            $this->flashLogger->success(Trans::__('Session resumed.'));

            return $this->loginFinish($userEntity);
        }

        $this->systemLogger->alert(sprintf('Attempt to login with an invalid token from %s', $this->remoteIP), ['event' => 'security']);

        return false;
    }

    /**
     * Get the user record entity if it exists.
     *
     * @param string $userName
     *
     * @return Entity\Users|null
     */
    protected function getUserEntity($userName)
    {
        if (!$userEntity = $this->repositoryUsers->getUser($userName)) {
            $this->flashLogger->error(Trans::__('Your account is disabled. Sorry about that.'));

            return null;
        }

        if (!$userEntity->getEnabled()) {
            $this->systemLogger->alert("Attempt to login with disabled account by '$userName'", ['event' => 'security']);
            $this->flashLogger->error(Trans::__('Your account is disabled. Sorry about that. and stuff'));

            return null;
        }

        return $userEntity;
    }

    /**
     * Finish user login process(es).
     *
     * @param Entity\Users $userEntity
     *
     * @return boolean
     */
    protected function loginFinish(Entity\Users $userEntity)
    {
        if (!$this->updateUserLogin($userEntity)) {
            return false;
        }

        $userEntity->setPassword('**dontchange**');
        $tokenEntity = $this->updateAuthToken($userEntity);
        $token = new Token($userEntity, $tokenEntity);

        $this->session->set('authentication', $token);
        $this->session->save();

        return true;
    }

    /**
     * Add error messages to logs and update the user.
     *
     * @param Entity\Users $userEntity
     */
    protected function loginFailed(Entity\Users $userEntity)
    {
        $this->flashLogger->error(Trans::__('Username or password not correct. Please check your input.'));
        $this->systemLogger->info("Failed login attempt for '" . $userEntity->getDisplayname() . "'.", ['event' => 'authentication']);

        // Update the failed login attempts, and perhaps throttle the logins.
        $userEntity->setFailedlogins($userEntity->getFailedlogins() + 1);
        $userEntity->setThrottleduntil($this->throttleUntil($userEntity->getFailedlogins() + 1));
        unset($userEntity->password);
        $this->repositoryUsers->save($userEntity);
    }

    /**
     * Update the user record with latest login information.
     *
     * @param Entity\Users $userEntity
     *
     * @return boolean
     */
    protected function updateUserLogin(Entity\Users $userEntity)
    {
        $userEntity->setLastseen(Carbon::now());
        $userEntity->setLastip($this->remoteIP);
        $userEntity->setFailedlogins(0);
        $userEntity->setThrottleduntil($this->throttleUntil(0));
        $userEntity = $this->updateUserShadowLogin($userEntity);

        // Don't try to save the password on login
        unset($userEntity->password);
        if ($this->repositoryUsers->save($userEntity)) {
            $this->flashLogger->success(Trans::__("You've been logged on successfully."));

            return true;
        }

        return false;
    }

    /**
     * Remove expired shadow login data.
     *
     * @param Entity\Users $userEntity
     *
     * @return Entity\Users
     */
    protected function updateUserShadowLogin(Entity\Users $userEntity)
    {
        if (Carbon::now() > $userEntity->getShadowvalidity()) {
            $userEntity->setShadowpassword('');
            $userEntity->setShadowtoken('');
            $userEntity->setShadowvalidity(null);
        }

        return $userEntity;
    }

    /**
     * Set the Authtoken cookie and DB-entry. If it's already present, update it.
     *
     * @param Entity\Users $userEntity
     *
     * @return Entity\Authtoken
     */
    protected function updateAuthToken($userEntity)
    {
        $salt = $this->randomGenerator->generateString(32);

        if (!$tokenEntity = $this->repositoryAuthtoken->getUserToken($userEntity->getUsername(), $this->remoteIP, $this->userAgent)) {
            $tokenEntity = new Entity\Authtoken();
        }

        $username = $userEntity->getUsername();
        $token = $this->getAuthToken($username, $salt);
        $validityPeriod = $this->cookieOptions['lifetime'];

        $tokenEntity->setUsername($userEntity->getUsername());
        $tokenEntity->setToken($token);
        $tokenEntity->setSalt($salt);
        $tokenEntity->setValidity(Carbon::create()->addSeconds($validityPeriod));
        $tokenEntity->setIp($this->remoteIP);
        $tokenEntity->setLastseen(Carbon::now());
        $tokenEntity->setUseragent($this->userAgent);

        $this->repositoryAuthtoken->save($tokenEntity);

        $this->systemLogger->debug("Saving new login token '$token' for user ID '$username'", ['event' => 'authentication']);

        return $tokenEntity;
    }

    /**
     * Calculate the amount of time until we should throttle login attempts for
     * a user.
     *
     * The amount is increased exponentially with each attempt: 1, 4, 9, 16, 25,
     * 36… seconds.
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

            return Carbon::create()->addSeconds($wait);
        }
    }
}
