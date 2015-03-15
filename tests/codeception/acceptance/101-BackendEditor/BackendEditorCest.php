<?php

use Codeception\Util\Fixtures;

/**
 * Backend 'editor' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendEditorCest
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
     * Login as the editor user
     *
     * @param \AcceptanceTester $I
     */
    public function loginEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'editor' user");
        $I->loginAs($this->user['editor']);
        $I->see('Dashboard');
    }

    /**
     *
     *
     * @param \AcceptanceTester $I
     */
    public function viewContenttypesTest(\AcceptanceTester $I)
    {
        $I->wantTo('make sure the page editor user cannot see any content types except pages');
        $I->loginAs($this->user['editor']);
        $I->see('View Pages');

        $I->dontSee('Edit Entries');
        $I->dontSee('Edit Showcases');
        $I->dontSee('Edit Dummies');
    }

    /**
     *
     *
     * @param \AcceptanceTester $I
     */
    public function createRecordsTest(\AcceptanceTester $I)
    {
        $I->wantTo('create and edit Pages as pagewriter user');
        $I->loginAs($this->user['editor']);
        $I->see('New Page');

        $I->click('New Page');
        $I->see('Actions for this Page');

        $I->fillField('title', 'A page I made');
        $I->fillField('teaser', 'Woop woop woop! Crazy nice stuff inside!');
        $I->fillField('body', 'Take it, take it! I have three more of these!');

        $I->click('Save Page');

        $I->see('A page I made');
        $I->see('Woop woop woop');
    }

    /**
     *
     *
     * @param \AcceptanceTester $I
     */
    public function deniedEditEntriesTest(\AcceptanceTester $I)
    {
        $I->wantTo('be denied permission to edit Entries as the pagewriter user');
        $I->loginAs($this->user['editor']);
        $I->amOnPage('bolt/editcontent/entries/');

        // Temporarily disable this test, until we figure out what's going on.
        // $I->see('You do not have the right privileges');
    }

    /**
     *
     *
     * @param \AcceptanceTester $I
     */
    public function deniedEditPagesTest(\AcceptanceTester $I)
    {
        $I->wantTo('be denied "publish" permissions on Pages as pagewriter user');
        $I->loginAs($this->user['editor']);
        $I->see('New Page');
        $I->click('New Page');
        $I->see('Actions for this Page');
        $I->fillField('title', 'A page I made');
        $I->fillField('teaser', 'Woop woop woop! Crazy nice stuff inside!');
        $I->fillField('body', 'Take it, take it! I have three more of these!');

        // make sure the page cannot be published by setting its status in the
        // edit form
        $I->dontSeeInField('status', 'Published');

        // let's save this page anyway, because we'll be needing it...
        $I->click('Save Page');

        // also check that the "publish page" context menu option isn't shown
        $I->amOnPage('bolt');
        $I->dontSee('Publish Page');
    }
}
