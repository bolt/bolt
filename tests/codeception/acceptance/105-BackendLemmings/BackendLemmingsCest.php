<?php

use Codeception\Util\Fixtures;

/**
 * Backend 'lemmings' test(s)
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendLemmingsCest
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
     * Login as the lemmings user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginLemmingsTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'lemmings' user");

        $I->loginAs($this->user['lemmings']);
        $this->cookies[$this->tokenNames['authtoken']] = $I->grabCookie($this->tokenNames['authtoken']);
        $this->cookies[$this->tokenNames['session']] = $I->grabCookie($this->tokenNames['session']);

        $I->see('Dashboard');
    }

    /**
     * Test that a user with no access rights to Dashboard gets redirected to
     * the hmoepage.
     *
     * Inspired by the Atari game Lemmings and the Eddie Vedder commment:
     * "Longest song title in the Pearl Jam catalogue", referencing the song
     * "Elderly Woman Behind the Counter in a Small Town", and the name of the
     * particular unit test method until Bolt 2.3…
     *
     * @param \AcceptanceTester $I
     */
    public function dashboardWithoutPermissionRedirectsToHomepageTest(\AcceptanceTester $I)
    {
        $I->wantTo('Set permissions/global/dashboard to empty and be redirected to the homepage');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/permissions.yml');

        $yaml = $I->getLemmingsPermissions();

        $I->fillField('#form_contents', $yaml);

        $token = $I->grabValueFrom('#form__token');
        $I->sendAjaxPostRequest('/bolt/file/edit/config/permissions.yml', [
            'form[_token]'   => $token,
            'form[contents]' => $yaml
        ]);

        // Verify we go to the dashboard and end up on the homepage
        $I->amOnPage('/bolt');

        $I->see('A sample site');
        $I->see('Recent Pages');
        $I->dontSee('Recent Resources');

        $I->see('A Page I Made', 'h1');
        $I->see('Built with Bolt, tested with Codeception', 'footer');
    }
}
