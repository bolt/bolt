<?php

use Codeception\Util\Locator;

/**
 * Backend 'developer' tests.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendDeveloperCest extends AbstractAcceptanceTest
{
    /**
     * Login as the developer user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginDeveloperTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'developer' user");

        $I->loginAs($this->user['developer']);
        $this->saveLogin($I);

        $I->see('Dashboard');
    }

    /**
     * Test the 'File management -> Uploaded Files' interface.
     *
     * @param \AcceptanceTester $I
     */
    public function fileManagementUploadedFilesTest(\AcceptanceTester $I)
    {
        $I->wantTo("Use the 'File management -> Uploaded Files' interface as the 'developer' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/files');

        $file = 'index.html';
        $I->see('Create folder', Locator::find('a', ['href' => '#']));
        $I->see($file, Locator::href("/bolt/file/edit/files/$file"));

        $I->see('4 B', 'td');
        $I->see("Rename $file",    Locator::find('a', ['href' => '#']));
        $I->see("Delete $file",    Locator::find('a', ['href' => '#']));
        $I->see("Duplicate $file", Locator::find('a', ['href' => '#']));
    }

    /**
     * Test the 'File management -> View / edit templates' interface.
     *
     * @param \AcceptanceTester $I
     */
    public function fileManagementViewEditTemplatesTest(\AcceptanceTester $I)
    {
        $I->wantTo("Use the 'File management -> View / edit templates' interface as the 'developer' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/files/themes');

        // Inspect the landing page
        $dir = 'base-2018';
        $I->see('Create folder', Locator::find('a', ['href' => '#']));
        $I->see($dir,            Locator::href("/bolt/files/themes/$dir"));
        $I->see("Rename $dir",   Locator::find('a', ['href' => '#']));
        $I->see("Delete $dir",   Locator::find('a', ['href' => '#']));

        // Navigate into the theme and check the results
        $I->click("$dir",     Locator::href("/bolt/files/themes/$dir"));
        $I->see('css',        Locator::href("/bolt/files/themes/$dir/css"));
        $I->see('partials',   Locator::href("/bolt/files/themes/$dir/partials"));
        $I->see('js',         Locator::href("/bolt/files/themes/$dir/js"));
        $I->see('theme.yml',  Locator::href("/bolt/file/edit/themes/$dir/theme.yml"));
        $I->see('page.twig',  Locator::href("/bolt/file/edit/themes/$dir/page.twig"));
        $I->see('index.twig', Locator::href("/bolt/file/edit/themes/$dir/index.twig"));

        // Navigate into a subdirectory
        $I->click('css',     Locator::href("/bolt/files/themes/$dir/css"));
        $I->see('theme.css', Locator::href("/bolt/file/edit/themes/$dir/css/theme.css"));
    }

    /**
     * Test edit a template file and save it.
     *
     * @param \AcceptanceTester $I
     */
    public function editTemplateTest(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can edit and save the partials/_footer.twig template file.");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/themes/base-2018/partials/_footer.twig');

        // Put _footer.twig into edit mode
        $I->see('<footer role="contentinfo" class="footer">', 'textarea');

        // Edit the field
        $twig = $I->grabTextFrom('#file_edit_contents', 'textarea');
        $twig = str_replace('This website is ', 'This website is tested with Codeception, ', $twig);
        $I->fillField('#file_edit_contents', $twig);

        // Save it
        $token = $I->grabValueFrom('#file_edit__token');
        $I->sendAjaxPostRequest('/bolt/file/edit/themes/base-2018/partials/_footer.twig', [
            'file_edit' => [
                'contents' => $twig,
                '_token'   => $token,
                'save'     => true,
            ],
        ]);

        $I->amOnPage('/bolt/file/edit/themes/base-2018/partials/_footer.twig');
        $I->see('This website is tested with Codeception, ', '#file_edit_contents');
    }

    /**
     * Test that the 'developer' user can edit and save a translation.
     *
     * @param \AcceptanceTester $I
     */
    public function editTranslationsMessages(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can edit and save a translation.");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/tr');

        // Go into edit mode
        $I->see('page.login.button.forgot-password', 'textarea');

        // Edit the field
        $twig = $I->grabTextFrom('#file_edit_contents', 'textarea');
        $twig = '"Built with Bolt, tested with Codeception" : "Built with Bolt, tested with Codeception"' . PHP_EOL . $twig;
        $I->fillField('#file_edit_contents', $twig);

        // Save it
        $I->submitForm('form[name="file_edit"]', ['file_edit' => ['save' => 1]]);

        $I->amOnPage('/bolt/tr');
        $I->see('Built with Bolt, tested with Codeception', '#file_edit_contents');
    }

    /**
     * Test that the 'developer' user can edit translation long messages.
     *
     * @param \AcceptanceTester $I
     */
    public function editTranslationsLongMessages(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can edit translation long messages.");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/tr/infos');

        // Go into edit mode
        $I->see('Use this field to upload a photo or image', 'textarea');
        $twig = $I->grabTextFrom('#file_edit_contents', 'textarea');
        $twig = str_replace('Use this field to upload a photo or image', 'Use this field to upload a photo of a kitten', $twig);
        $I->fillField('#file_edit_contents', $twig);

        // Save it
        $I->submitForm('form[name="file_edit"]', ['file_edit' => ['save' => 1]]);

        $I->amOnPage('/bolt/tr/infos');
        $I->see('Use this field to upload a photo of a kitten', 'textarea');
    }

    /**
     * Test that the 'developer' user can view installed extensions.
     *
     * @param \AcceptanceTester $I
     */
    public function viewInstalledExtensions(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can view installed extensions.");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/extensions');

        $I->see('Currently Installed Extensions', 'h2');
        $I->see('Install a new Extension',        'h2');
        $I->see('Run update check',               'a');
        $I->see('Run all Updates',                'a');
        $I->see('Install all Packages',           'a');
    }
}
