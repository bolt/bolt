<?php

use Codeception\Util\Locator;

/**
 * Backend 'editor' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendEditorCest extends AbstractAcceptanceTest
{
    /**
     * Login as the editor user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'editor' user");

        $I->loginAs($this->user['editor']);
        $this->saveLogin($I);

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

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

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

        $I->see('Uploaded files', Locator::href('/bolt/files'));
        $I->dontSee('File Management', Locator::href('/bolt/files'));
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

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('New Page');

        $I->click('New Page');
        $I->see('Pages',      Locator::href('/bolt/overview/pages'));
        $I->see('View Pages', Locator::href('/bolt/overview/pages'));
        $I->see('New Page',   Locator::href('/bolt/editcontent/pages'));

        $I->fillField('#title',  'A page I made');
        $I->fillField('#slug',   'a-page-i-made');
        $I->fillField('#teaser', 'Woop woop woop! Crazy nice stuff inside!');
        $I->fillField('#body',   'Take it, take it! I have three more of these!');

        $I->click('Save Page', '#savecontinuebutton');
        $I->see('The new Page has been saved.');

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
        $I->wantTo('Check the PRE_SAVE & POST_SAVE StorageEvent triggered correctly on create');

        // Set up the browser
        $this->setLoginCookies($I);
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

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/editcontent/pages/1');

        $I->see('Actions for this Page');

        // Make sure the page cannot be published by setting its status
        $I->seeInField('#statusselect', 'draft');
        $I->dontSeeInField('#statusselect', 'published');

        // Save the page and return to the overview
        $I->click('Save & return to overview');
        $I->see('Actions for Pages', '.panel-heading');

        // Check the 'Publish page' context menu option isn't shown
        $I->dontSee('Publish Page', 'a');

        // Check the 'Duplicate page' context menu option is shown
        $I->see('Duplicate Page', 'a');
    }

    /**
     * Check that the PRE_SAVE and POST_SAVE storage event triggered on save.
     *
     * @param \AcceptanceTester $I
     */
    public function checkSaveRecordsEventTest(\AcceptanceTester $I)
    {
        $I->wantTo('Check the PRE_SAVE & POST_SAVE StorageEvent triggered correctly on save');

        // Set up the browser
        $this->setLoginCookies($I);
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

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/editcontent/entries/');

        $I->see('You do not have the right privileges');
    }

    /**
     * Create an 'About' page record.
     *
     * @param \AcceptanceTester $I
     */
    public function createAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create an 'About' page as the 'editor' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('New Page');
        $I->click('New Page');

        $teaser = file_get_contents(CODECEPTION_DATA . '/about.teaser.html');
        $body   = file_get_contents(CODECEPTION_DATA . '/about.body.html');

        $I->fillField('#title',  'About');
        $I->fillField('#slug',   'about');
        $I->fillField('#teaser', $teaser);
        $I->fillField('#body',   $body);

        $I->click('Save Page', '#savecontinuebutton');

        $I->see('The new Page has been saved.');
        $I->see("Easy for editors, and a developer's dream cms");
        // Note: Due to the change in #3859 this breaks on Composer based tests
        // for PHP 5.4 and 5.5 as the full sentence gets clipped… Go figure!
        $I->see('Quick to set up and easily');
    }

    /**
     * Create a contact page with templatefields
     *
     * @param \AcceptanceTester $I
     */
    public function checkTemplateFieldsTest(\AcceptanceTester $I)
    {
        $I->wantTo('Create a contact page with templatefields');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('New Page');
        $I->click('New Page');

        $I->fillField('#title',       'Contact Page');
        $I->fillField('#slug',        'contact');
        $I->selectOption('#template', 'extrafields.twig');

        $I->click('Save Page', '#savecontinuebutton');
        $I->see('The new Page has been saved.');
        $I->click('CONTACT PAGE');

        // Page has been saved, fill templatefields
        $I->see('Template', 'a[data-toggle=tab]');

        $I->fillField('#templatefields-section_1', 'This is the contact text');
        $I->click('Save Page', '#savecontinuebutton');

        $I->click('CONTACT PAGE');
        /*
         * In v2.0.13 Codeception made the awesome decision to refactor their
         * PHP Browser code — in a patch release no less — and it doesn't
         * properly handle URL queries parameters in POSTs. For now we'll just
         * pretend that seeing the data is good enough…
         */
        $I->seeInSource('This is the contact text');
//         $I->seeInField('#templatefields-section_1', 'This is the contact text');
    }
}
