<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Locator;

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
     * Login as the editor user.
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
     * Check what the editor can and can't see.
     *
     * @param \AcceptanceTester $I
     */
    public function viewMenusTest(\AcceptanceTester $I)
    {
        $I->wantTo('make sure the page editor user can only see certain menus');
        $I->loginAs($this->user['editor']);

        $I->see('View Pages');
        $I->see('New Page');

        $I->dontSee('View Entries');
        $I->dontSee('New Entry');

        $I->see('View Showcases');
        $I->dontSee('New Showcase');

        $I->dontSee('Configuration', Locator::href('/bolt/users'));
        $I->dontSee('Translations', Locator::href('/bolt/tr'));
        $I->dontSee('Extras', Locator::href('/bolt/extend'));
        $I->dontSee('Latest system activity');
        $I->dontSee('Edit Dummies');

        $I->see('File Management', Locator::href('/bolt/files'));
        $I->see('Uploaded files', Locator::href('/bolt/files'));
        $I->dontSee('View/edit templates', Locator::href('/bolt/theme'));
    }

    /**
     * Create a page record.
     *
     * @param \AcceptanceTester $I
     */
    public function createRecordsTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create and edit Pages as the 'editor' user");
        $I->loginAs($this->user['editor']);
        $I->see('New Page');

        $I->click('New Page');
        $I->see('Pages',      Locator::href('/bolt/overview/pages'));
        $I->see('View Pages', Locator::href('/bolt/overview/pages'));
        $I->see('New Page',   Locator::href('/bolt/editcontent/pages'));

        $I->fillField('#title',  'A page I made');
        $I->fillField('#teaser', 'Woop woop woop! Crazy nice stuff inside!');
        $I->fillField('#body',   'Take it, take it! I have three more of these!');

        $I->click('Save Page');

        $I->see('A page I made');
        $I->see('Woop woop woop');
    }

    /**
     * Check that the PRE_SAVE and POST_SAVE storage event triggered on create.
     *
     * @param \AcceptanceTester $I
     */
    public function checkCreateRecordsEventTest(\AcceptanceTester $I)
    {
        $I->wantTo("Check the PRE_SAVE & POST_SAVE StorageEvent triggered correctly on create");
        $I->loginAs($this->user['editor']);

        $I->amOnPage('/bolt/editcontent/pages/1');

        $I->seeInField('#title',  'A PAGE I MADE');
        $I->see('Snuck in to teaser during PRE_SAVE on create');
        $I->see('Snuck in to body during POST_SAVE on create');
    }

    /**
     * Check that the editor can't publish Entries
     *
     * @param \AcceptanceTester $I
     */
    public function deniedPublishPagesTest(\AcceptanceTester $I)
    {
        $I->wantTo("be denied permission to publish Pages as the 'editor' user");
        $I->loginAs($this->user['editor']);

        $I->amOnPage('/bolt/editcontent/pages/1');

        $I->see('Actions for this Page');

        // Make sure the page cannot be published by setting its status
        $I->seeInField('#statusselect', 'draft');
        $I->dontSeeInField('#statusselect', 'published');

        // Save the page and return to the overview
        $I->click('Save & return to overview');
        $I->see('Actions for Pages');

        // Check the 'Publish page' context menu option isn't shown
        $I->dontSee('Publish Page');

        // Check the 'Duplicate page' context menu option is shown
        $I->see('Duplicate Page');
    }

    /**
     * Check that the PRE_SAVE and POST_SAVE storage event triggered on save.
     *
     * @param \AcceptanceTester $I
     */
    public function checkSaveRecordsEventTest(\AcceptanceTester $I)
    {
        $I->wantTo("Check the PRE_SAVE & POST_SAVE StorageEvent triggered correctly on save");
        $I->loginAs($this->user['editor']);

        $I->amOnPage('/bolt/editcontent/pages/1');

        $I->seeInField('#title',  'A Page I Made');
        $I->see('Added to teaser during PRE_SAVE on save');
        $I->see('Added to body during POST_SAVE on save');
    }

    /**
     * Check that the editor can't create Entries
     *
     * @param \AcceptanceTester $I
     */
    public function deniedEditEntriesTest(\AcceptanceTester $I)
    {
        $I->wantTo("be denied permission to edit Entries as the 'editor' user");
        $I->loginAs($this->user['editor']);
        $I->amOnPage('bolt/editcontent/entries/');
        $I->see('You do not have the right privileges');
    }
}
