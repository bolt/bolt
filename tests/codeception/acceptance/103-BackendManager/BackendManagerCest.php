<?php

use Codeception\Util\Fixtures;

/**
 * Backend 'manager' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendManagerCest
{
    /** @var array */
    protected $user;

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
     * Login as the manager user
     *
     * @param \AcceptanceTester $I
     */
    public function loginManagerTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'manager' user");
        $I->loginAs($this->user['manager']);
        $I->see('Dashboard');
    }
}
