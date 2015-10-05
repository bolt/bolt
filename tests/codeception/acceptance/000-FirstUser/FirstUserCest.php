<?php

use Codeception\Util\Fixtures;

/**
 * First user site setup tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FirstUserCest
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
     * Create the first site user
     *
     * @param \AcceptanceTester $I
     */
    public function createFirstUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('create the first user');

        // Ensure we're on the first user page
        $I->amOnPage('/');
        $I->see('Please create the first user');

        // Fill in the form and submit
        $I->fillField('form[username]',              $this->user['admin']['username']);
        $I->fillField('form[password]',              $this->user['admin']['password']);
        $I->fillField('form[password_confirmation]', $this->user['admin']['password']);
        $I->fillField('form[email]',                 $this->user['admin']['email']);
        $I->fillField('form[displayname]',           $this->user['admin']['displayname']);

        $I->click('input[type=submit]');

        // We should now be logged in an greeted!
        $I->see('Welcome to your new Bolt site');
    }
}
