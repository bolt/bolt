<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Locator;

/**
 * Backend 'admin' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAdminLoginWithEmailCest
{
    /** @var array */
    protected $user;
    /** @var array */
    protected $tokenNames;

    /** @var array */
    private $cookies = [];

    /**
     * @param \AcceptanceTester $I
     */
    public function _before(\AcceptanceTester $I)
    {
        $this->user = Fixtures::get('users');
        $this->tokenNames = Fixtures::get('tokenNames');
    }

    /**
     * @param \AcceptanceTester $I
     */
    public function _after(\AcceptanceTester $I)
    {
    }

    /**
     * Login the admin user using his email
     *
     * @param \AcceptanceTester $I
     */
    public function loginAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log into the backend as Admin with email');

        $I->loginWithEmailAs($this->user['admin']);

        $this->cookies[$this->tokenNames['authtoken']] = $I->grabCookie($this->tokenNames['authtoken']);
        $this->cookies[$this->tokenNames['session']] = $I->grabCookie($this->tokenNames['session']);

        $I->see('Dashboard');
        $I->see('Configuration', Locator::href('/bolt/users'));
        $I->see("You've been logged on successfully.");
    }
}
