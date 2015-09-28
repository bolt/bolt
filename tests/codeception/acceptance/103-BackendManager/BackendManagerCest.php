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
     * Login as the manager user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginManagerTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'manager' user");

        $I->loginAs($this->user['manager']);
        $this->cookies[$this->tokenNames['authtoken']] = $I->grabCookie($this->tokenNames['authtoken']);
        $this->cookies[$this->tokenNames['session']] = $I->grabCookie($this->tokenNames['session']);

        $I->see('Dashboard');
    }

    /**
     * Publish the 'Home' page.
     *
     * @param \AcceptanceTester $I
     */
    public function publishHomePageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Publish the 'About' page as 'manager' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/editcontent/pages/1');

        $I->see('Woop woop woop!');
        $I->see('Crazy nice stuff inside!');

        $I->selectOption('#statusselect', 'published');

        $I->click('Save', '#savecontinuebutton');

        $I->see('The changes to the Page have been saved.');
    }

    /**
     * Publish the 'About' page.
     *
     * @param \AcceptanceTester $I
     */
    public function publishAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Publish the 'About' page as 'manager' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/editcontent/pages/2');

        $I->see("Easy for editors, and a developer's dream cms");
        $I->see('Quick to set up and easily extendible');

        $I->selectOption('#statusselect', 'published');

        $I->click('Save', '#savecontinuebutton');

        $I->see('The changes to the Page have been saved.');
    }

    /**
     * Publish the 'Contact' page.
     *
     * @param \AcceptanceTester $I
     */
    public function publishContactPageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Publish the 'Contact' page with 'templatefields' as 'manager' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/editcontent/pages/3');

        $I->see('This is the contact text');

        $I->selectOption('#statusselect', 'published');

        $I->click('Save', '#savecontinuebutton');

        $I->see('The changes to the Page have been saved.');
    }
}
