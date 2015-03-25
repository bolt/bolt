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
     * Login as the manager user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginManagerTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'manager' user");
        $I->loginAs($this->user['manager']);
        $I->see('Dashboard');
    }

    /**
     * Publish the 'About' page.
     *
     * @param \AcceptanceTester $I
     */
    public function publishAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Publish the 'About' page as 'manager' user");
        $I->loginAs($this->user['manager']);

        $I->amOnPage('bolt/editcontent/pages/2');

        $I->see("Easy for editors, and a developer's dream cms");
        $I->see('Quick to set up and easily extendible');

        $I->selectOption('#statusselect', 'published');

        $I->click('Save', '#savecontinuebutton');

        $I->see('The changes to this Page have been saved.');
    }
}
