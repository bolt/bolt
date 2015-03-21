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
    }

    /**
     * @param \AcceptanceTester $I
     */
    public function _after(\AcceptanceTester $I)
    {
    }

    /**
     * Check the homepage
     *
     * @param \AcceptanceTester $I
     */
    public function checkFrontPageTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that the homepage works');

        $I->amOnPage('');

        $I->see('A sample site');
        $I->see('Recent Pages');
        $I->see('Recent Entries');
        $I->see('Recent Showcases');
        $I->dontSee('Recent Typewriters');
    }

    /**
     * Check the about page and pagebind route
     *
     * @param \AcceptanceTester $I
     */
    public function checkAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that the about page and pagebind route works');

        $I->amOnPage('about');

        $I->see("Easy for editors, and a developer's dream cms", 'h1');
        $I->see('Bolt is an open source Content Management Tool', 'h2');
        $I->see('The fully responsive dashboard works on desktop computers, laptops, tablets and mobile phones alike, so you can control anything from wherever you are.', 'p');
    }
}
