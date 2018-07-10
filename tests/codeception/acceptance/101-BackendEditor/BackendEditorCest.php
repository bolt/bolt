<?php

use Codeception\Util\Locator;

/**
 * Backend 'editor' tests.
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
     * Create a homepage record.
     *
     * @param \AcceptanceTester $I
     */
    public function createHomepageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create and edit the Homepage as the 'editor' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('New Homepage');

        $I->click('New Homepage');
        $I->see('Homepage',      Locator::href('/bolt/editcontent/homepage'));

        $I->fillField('#title',   'Welcome Home (Sanitarium)');
        $I->fillField('#slug',    'welcome-home-sanitarium');
        $I->fillField('#teaser',  'Welcome to where time stands still');
        $I->fillField('#content', 'No one leaves and no one will');

        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save' => 1]]);
        $I->see('The new Homepage has been saved.');

        $I->see('Welcome Home (Sanitarium)');
        $I->see('Welcome to where time stands still');
        $I->see('No one leaves and no one will');

        $I->seeOptionIsSelected('#statusselect', 'Published');
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

        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save' => 1]]);
        $I->see('The new Page has been saved.');

        $I->see('A page I made');
        $I->see('Woop woop woop');
    }

    /**
     * Check that the editor can't publish Entries.
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
        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save_return' => 1]]);
        $I->see('Actions for Pages', '.panel-heading');

        // Check the 'Publish page' context menu option isn't shown
        $I->dontSee('Publish Page', 'a');

        // Check the 'Duplicate page' context menu option is shown
        $I->see('Duplicate Page', 'a');
    }

    /**
     * Check that the editor can't create Entries.
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
        $body = file_get_contents(CODECEPTION_DATA . '/about.body.html');

        $I->fillField('#title',  'About');
        $I->fillField('#slug',   'about');
        $I->fillField('#teaser', $teaser);
        $I->fillField('#body',   $body);

        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save' => 1]]);

        $I->see('The new Page has been saved.');
        $I->see("Easy for editors, and a developer's dream cms");
        // Note: Due to the change in #3859 this breaks on Composer based tests
        // for PHP 5.4 and 5.5 as the full sentence gets clipped… Go figure!
        $I->see('Quick to set up and easily');
    }

    /**
     * Create a contact page with TemplateFields.
     *
     * @param \AcceptanceTester $I
     */
    public function checkTemplateFieldsTest(\AcceptanceTester $I)
    {
        $I->wantTo('Create a contact page with TemplateFields');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('New Page');
        $I->click('New Page');

        $I->fillField('#title',       'Contact Page');
        $I->fillField('#slug',        'contact');
        $I->selectOption('#template', 'page.twig');

        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save' => 1]]);
        $I->see('The new Page has been saved.');
        $I->click('Contact Page');

        // Page has been saved, fill TemplateFields
        $I->see('Template', 'a[data-toggle=tab]');

        $I->fillField('#templatefields-text', 'This is the contact text');
        $I->fillField('#templatefields-html', '<p>HTML for Drop Bears</p>');
        // Disabled as we currently don't set a HTML ID :-/
        //$I->fillField('#templatefields-textarea', 'What about a textarea');
        $I->fillField('#templatefields-markdown', '## Some markdown');
        // Disabled as we currently don't set a HTML ID :-/
        //$I->fillField('#templatefields-geolocation', 'Prins Hendrikstraat 91');
        //$I->fillField('#templatefields-video', 'https://www.youtube.com/watch?v=4qlCC1GOwFw');
        $I->fillField('#field-templatefields-image', '2017-07/vetted-image.jpg');
        $I->fillField('#templatefields-image-title', 'Known good image');
        // Disabled as we need to buy HTML for Dummies
        //$I->fillField('#templatefields-imagelist', '');
        //$I->fillField('#templatefields-file', '');
        //$I->fillField('#templatefields-filelist', '');
        // Disabled as this used only an unpredictable "BUID"
        //$I->checkOption('#templatefields-checkbox_1', '1');
        $I->fillField('#templatefields-integer', '42');
        $I->fillField('#templatefields-float', '4.2');
        $I->selectOption('#templatefields-select_map', 'home');
        $I->selectOption('#templatefields-select_list', 'foo');
        $I->selectOption('#templatefields-select_multi', ['Donatello', 'Rafael']);
        $I->selectOption('#templatefields-select_record', '1');
        $I->selectOption('#templatefields-select_record_single', '2');
        $I->selectOption('#templatefields-select_record_keys', 'contact');

        $I->click('Save Page', '#content_edit_save');

        $I->click('Contact Page');

        $I->seeInField('#templatefields-text', 'This is the contact text');
        $I->seeInField('#templatefields-html', '<p>HTML for Drop Bears</p>');
        // Disabled as we currently don't set a HTML ID :-/
        //$I->seeInField('#templatefields-textarea', '');
        $I->seeInField('#templatefields-markdown', '## Some markdown');
        // Disabled as we currently don't set a HTML ID :-/
        //$I->seeInField('#templatefields-geolocation', 'Prins Hendrikstraat 91');
        //$I->seeInField('#templatefields-video', 'https://www.youtube.com/watch?v=4qlCC1GOwFw');
        $I->seeInField('#field-templatefields-image', '2017-07/vetted-image.jpg');
        $I->seeInField('#templatefields-image-title', 'Known good image');
        $I->seeInField('#templatefields-integer', '42');
        $I->seeInField('#templatefields-float', '4.2');
        $I->seeInField('#templatefields-select_map', 'home');
        $I->seeInField('#templatefields-select_list', 'foo');
        $I->seeInField('#templatefields-select_multi', 'Donatello');
        $I->seeInField('#templatefields-select_multi', 'Rafael');
        $I->seeInField('#templatefields-select_record', '1');
        $I->seeOptionIsSelected('#templatefields-select_record', '1 / A page I made');
        $I->seeInField('#templatefields-select_record_single', '2');
        $I->seeOptionIsSelected('#templatefields-select_record_single', 'About');
        $I->seeInField('#templatefields-select_record_keys', 'contact');
        $I->seeOptionIsSelected('#templatefields-select_record_keys', 'Contact Page');
    }
}
