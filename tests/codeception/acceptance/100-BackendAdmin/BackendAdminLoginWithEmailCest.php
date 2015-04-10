<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Locator;

/**
 * Backend 'admin' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAdminCest
{
    /** @var array */
    protected $user;

    /** @var array */
    private $cookies = array('bolt_authtoken' => '', 'bolt_session' => '');

    /**
     * @param \AcceptanceTester $I
     */
    public function _before(\AcceptanceTester $I)
    {
        $this->user = Fixtures::get('users');
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

        $this->cookies['bolt_authtoken'] = $I->grabCookie('bolt_authtoken');
        $this->cookies['bolt_session'] = $I->grabCookie('bolt_session');

        $I->see('Dashboard');
        $I->see('Configuration', Locator::href('/bolt/users'));
        $I->see("You've been logged on successfully.");
    }
}
