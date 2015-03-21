<?php

use Codeception\Util\Fixtures;

/**
 * Frontend navigation and render tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FrontendCest
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
        $I->wantTo('see that the frontpage works');

        $I->amOnPage('');

        $I->see('A sample site');
        $I->see('Recent Pages');
        $I->see('Recent Entries');
        $I->see('Recent Showcases');
    }
}
