<?php

use Codeception\Util\Fixtures;

/**
 * Backend 'author' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAuthorCest
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
     * Login as the author user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'author' user");

        $I->loginAs($this->user['author']);
        $this->cookies[$this->tokenNames['authtoken']] = $I->grabCookie($this->tokenNames['authtoken']);
        $this->cookies[$this->tokenNames['session']] = $I->grabCookie($this->tokenNames['session']);

        $I->see('Dashboard');
    }

    /**
     * Create an 'About' page record.
     *
     * @param \AcceptanceTester $I
     */
    public function editAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Edit the 'About' page as the 'author' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt');

        $I->see('Edit', 'a');
        $I->click('Edit', 'a');

        $I->click('Save Page', '#savecontinuebutton');

        $I->see('The changes to the Page have been saved.');
    }

    /**
     * Search for the 'About' page record.
     *
     * @param \AcceptanceTester $I
     */
    public function omnisearchTest(\AcceptanceTester $I)
    {
        $I->wantTo("Search for the 'About' page as the 'author' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/omnisearch');

        $I->fillField('.col-md-8 .form-control', 'About');

        $I->click('Search', '.col-md-8 [type="submit"]');

        $I->seeLink('Edit', '/bolt/editcontent/pages/2');
    }
}
