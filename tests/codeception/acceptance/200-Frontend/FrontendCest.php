<?php

use Codeception\Util\Fixtures;

/**
 * Frontend navigation and render tests.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FrontendCest
{
    /** @var array */
    protected $user;
    /** @var array */
    protected $tokenNames;

    /**
     * @param \AcceptanceTester $I
     */
    public function _before(\AcceptanceTester $I)
    {
        $this->tokenNames = Fixtures::get('tokenNames');
    }

    /**
     * @param \AcceptanceTester $I
     */
    public function _after(\AcceptanceTester $I)
    {
    }

    /**
     * Check the homepage.
     *
     * @param \AcceptanceTester $I
     */
    public function checkFrontPageTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that the homepage works.');

        $I->amOnPage('/');

        $I->see('A sample site');
        $I->see('Recent Pages');
        $I->dontSee('Recent Resources');

        $I->see('Welcome Home (Sanitarium)', 'h2');
        $I->see('This website is tested with Codeception, built with Bolt.', 'footer');
    }

    /**
     * Check that Bolt doesn't set any session cookies when we're not logged in.
     *
     * @param \AcceptanceTester $I
     */
    public function checkNoSessionCookieTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that there are no session cookies set.');

        $I->resetCookie($this->tokenNames['session']);

        $I->amOnPage('/');
        $I->dontSeeCookie($this->tokenNames['session']);

        $I->amOnPage('/thumbs/42x42c/koala.jpg');
        $I->dontSeeCookie($this->tokenNames['session']);
    }

    /**
     * Check the about page and pagebind route.
     *
     * @param \AcceptanceTester $I
     */
    public function checkAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that the about page and pagebind route works.');

        $I->amOnPage('/about');

        $I->see("Easy for editors, and a developer's dream cms", 'h1');
        $I->see('Bolt is an open source Content Management Tool', 'h2');
        $I->see('The fully responsive dashboard works on desktop computers, laptops, tablets and mobile phones alike, so you can control anything from wherever you are.', 'p');
    }

    /**
     * Check the contact page for templatefields.
     *
     * @param \AcceptanceTester $I
     */
    public function checkContactPageTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that the contact page and templatefields works');

        $I->amOnPage('/contact');

        $I->seeInSource('This is the contact text');
    }

    /**
     * Check a viewless contenttype can't be routed to.
     *
     * @param \AcceptanceTester $I
     */
    public function checkNotFoundViewlessTest(\AcceptanceTester $I)
    {
        $I->wantTo("see that a a viewless contenttype can't be routed to.");

        $I->amOnPage('/resources');

        $I->see('404 reasons to cry');
        $I->see('Well, this is kind of embarrassing!');
    }

    /**
     * Check a non-existing URL and check for our 404.
     *
     * @param \AcceptanceTester $I
     */
    public function checkNotFoundResourceTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that a non-existing URL request returns a valid 404 page.');

        $I->amOnPage('/derp-a-derp');

        $I->see('404 reasons to cry');
        $I->see('Well, this is kind of embarrassing!');
    }

    /**
     * Check that canonical links are the same on URIs by slug and ID.
     *
     * @param \AcceptanceTester $I
     */
    public function checkCanonicalTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that canonical links are the same on URIs by slug and ID.');

        $I->amOnPage('/page/about');
        $I->seeElement('link', ['rel' => 'canonical', 'href' => 'http://example.org/about']);

        $I->amOnPage('/page/2');
        $I->seeElement('link', ['rel' => 'canonical', 'href' => 'http://example.org/about']);
    }

    /**
     * Check that menus have a 'Home' with class 'first' and and active URI with
     * the class 'active'.
     *
     * @param \AcceptanceTester $I
     */
    public function checkMenusTest(\AcceptanceTester $I)
    {
        $I->wantTo("see that menus have 'first' and a correct 'active'.");

        $I->amOnPage('/');
        $I->seeElement('a', ['href' => '/', 'class' => 'navbar-item is-active first']);

        $I->amOnPage('/pages');
        $I->seeElement('a', ['href' => '/pages', 'class' => 'navbar-item is-active ']);
    }

    /**
     * Check that Bolt doesn't allow profiler access when we're not logged in.
     *
     * @param \AcceptanceTester $I
     */
    public function checkNoProfilerTest(\AcceptanceTester $I)
    {
        $I->wantTo('see that there no profiler route available.');

        $I->resetCookie($this->tokenNames['session']);

        $I->amOnPage('/_profiler');
        $I->seeResponseCodeIs(404);

        $I->amOnPage('/_profiler/empty/search/results?limit=10');
        $I->seeResponseCodeIs(404);
    }
}
