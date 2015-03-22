<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Locator;

/**
 * Backend 'developer' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendDeveloperCest
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
     * Login as the developer user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginDeveloperTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'developer' user");
        $I->loginAs($this->user['developer']);
        $I->see('Dashboard');
    }

    /**
     * Test the 'File management -> Uploaded Files' interface
     *
     * @param \AcceptanceTester $I
     */
    public function fileManagementUploadedFilesTest(\AcceptanceTester $I)
    {
        $I->wantTo("Use the 'File management -> Uploaded Files' interface as the 'developer' user");
        $I->loginAs($this->user['developer']);

        $I->amOnPage('bolt/files');

        $file = 'blur-flowers-home-1093.jpg';
        $I->see('Create folder', Locator::find('a', ['href' => '#']));
        $I->see($file, Locator::href("/thumbs/1000x1000r/$file"));

        $I->see('66.23 KiB', 'td');
        $I->see('800 × 533 px', 'td ');
        $I->see('Place on stack',  Locator::find('a', ['href' => '#']));
        $I->see("Rename $file",    Locator::find('a', ['href' => '#']));
        $I->see("Delete $file",    Locator::find('a', ['href' => '#']));
        $I->see("Duplicate $file", Locator::find('a', ['href' => '#']));
    }

    /**
     * Test the 'File management -> View / edit templates' interface
     *
     * @param \AcceptanceTester $I
     */
    public function fileManagementViewEditTemplatesTest(\AcceptanceTester $I)
    {
        $I->wantTo("Use the 'File management -> View / edit templates' interface as the 'developer' user");
        $I->loginAs($this->user['developer']);

        $I->amOnPage('bolt/files/theme');

        // Inspect the landing page
        $dir  = 'base-2014';
        $I->see('Create folder', Locator::find('a', ['href' => '#']));
        $I->see($dir,            Locator::href("/bolt/files/theme/$dir"));
        $I->see("Rename $dir",   Locator::find('a', ['href' => '#']));
        $I->see("Delete $dir",   Locator::find('a', ['href' => '#']));

        // Navigate into the theme and check the results
        $I->click("$dir/",      Locator::href("/bolt/files/theme/$dir"));
        $I->see('css/',         Locator::href("/bolt/files/theme/$dir/css"));
        $I->see('images/',      Locator::href("/bolt/files/theme/$dir/images"));
        $I->see('javascripts/', Locator::href("/bolt/files/theme/$dir/javascripts"));
        $I->see('config.yml',   Locator::href("/bolt/file/edit/theme/$dir/config.yml"));
        $I->see('entry.twig',   Locator::href("/bolt/file/edit/theme/$dir/entry.twig"));
        $I->see('index.twig',   Locator::href("/bolt/file/edit/theme/$dir/index.twig"));

        // Navigate into a subdirectory
        $I->click('css/',  Locator::href("/bolt/files/theme/$dir/css"));
        $I->see('app.css', Locator::href("/bolt/file/edit/theme/$dir/css/app.css"));
    }

    /**
     * Test edit a template file and save it
     *
     * @param \AcceptanceTester $I
     */
    public function editTemplateTest(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can edit and save the _footer.twig template file.");
        $I->loginAs($this->user['developer']);

        // Put _footer.twig into edit mode
        $I->amOnPage('bolt/file/edit/theme/base-2014/_footer.twig');
        $I->see('<footer class="large-12 columns">', 'textarea');

        // Edit the field
        $twig = $I->grabTextFrom('#form_contents', 'textarea');
        $twig = str_replace('Built with Bolt', 'Built with Bolt, tested with Codeception', $twig);
        $I->fillField('#form_contents', $twig);
        $I->click('#saveeditfile');
        $I->see("File 'base-2014/_footer.twig' has been saved.");
    }
}
