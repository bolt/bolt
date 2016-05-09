<?php

use Codeception\Util\Locator;

/**
 * Backend 'admin' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAdminLoginWithEmailCest extends AbstractAcceptanceTest
{
    /**
     * Login the admin user using his email
     *
     * @param \AcceptanceTester $I
     */
    public function loginAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log into the backend as Admin with email');

        $I->loginWithEmailAs($this->user['admin']);
        $this->saveLogin($I);

        $I->see('Dashboard');

        $I->see('Configuration', Locator::href('/bolt/users'));
        $I->see("You've been logged on successfully.");
    }
}
