<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Locator;

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
    private $cookies = ['bolt_authtoken' => '', 'bolt_session' => ''];

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
     * Login as the author user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'author' user");

        $I->loginAs($this->user['author']);
        $this->cookies['bolt_authtoken'] = $I->grabCookie('bolt_authtoken');
        $this->cookies['bolt_session'] = $I->grabCookie('bolt_session');

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
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('/bolt');

        $I->see('Edit', 'a');
        $I->click('Edit', 'a');

        $I->click('Save Page', '#savecontinuebutton');

        $I->see('The changes to the Page have been saved.');
    }
}
