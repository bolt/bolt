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

    /** @var array */
    private $cookies = array('bolt_authtoken' => '', 'bolt_session' => '');

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
        $this->cookies['bolt_authtoken'] = $I->grabCookie('bolt_authtoken');
        $this->cookies['bolt_session'] = $I->grabCookie('bolt_session');

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

        // Set up the browser
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
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

        // Set up the browser
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
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
     * Test edit a template file and save it.
     *
     * @param \AcceptanceTester $I
     */
    public function editTemplateTest(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can edit and save the _footer.twig template file.");

        // Set up the browser
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('bolt/file/edit/theme/base-2014/_footer.twig');

        // Put _footer.twig into edit mode
        $I->see('<footer class="large-12 columns">', 'textarea');

        // Edit the field
        $twig = $I->grabTextFrom('#form_contents', 'textarea');
        $twig = str_replace('Built with Bolt', 'Built with Bolt, tested with Codeception', $twig);
        $I->fillField('#form_contents', $twig);
        $I->click('#saveeditfile');
        $I->see("File 'base-2014/_footer.twig' has been saved.");
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
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('bolt/tr');

        // Go into edit mode
        $I->see('contenttypes.general.choose-an-entry', 'textarea');

        // Edit the field
        $twig = $I->grabTextFrom('#form_contents', 'textarea');
        $twig = '"Built with Bolt, tested with Codeception" : "Built with Bolt, tested with Codeception"' . PHP_EOL . $twig;
        $I->fillField('#form_contents', $twig);
        $I->click('#saveeditlocale');
        $I->see("File 'app/resources/translations/en_GB/messages.en_GB.yml' has been saved.");
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
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('bolt/tr/infos');

        // Go into edit mode
        $I->see('Use this field to upload a photo or image', 'textarea');

        // Save it
        $I->click('#saveeditlocale');
        $I->see("File 'app/resources/translations/en_GB/infos.en_GB.yml' has been saved.");
    }

    /**
     * Test that the 'developer' user can edit translation Contenttype messages.
     *
     * @param \AcceptanceTester $I
     */
    public function editTranslationsContenttypeMessages(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can edit translation Contenttype messages.");

        // Set up the browser
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('bolt/tr/contenttypes');

        // Go into edit mode
        $I->see('contenttypes.entries.text.recent-changes-one', 'textarea');

        // Save it
        $I->click('#saveeditlocale');
        $I->see("File 'app/resources/translations/en_GB/contenttypes.en_GB.yml' has been saved.");
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
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('bolt/extend');

        $I->see('Currently Installed Extensions', 'h2');
        $I->see('Install a new Extension',        'h2');
        $I->see('Run update check',               'a');
        $I->see('Run all Updates',                'a');
        $I->see('Install all Packages',           'a');
    }

    /**
     * Test that the 'developer' user can configure installed extensions.
     *
     * @param \AcceptanceTester $I
     */
    public function configureInstalledExtensions(\AcceptanceTester $I)
    {
        $I->wantTo("See that the 'developer' user can configure installed extensions.");

        // Set up the browser
        $I->setCookie('bolt_authtoken', $this->cookies['bolt_authtoken']);
        $I->setCookie('bolt_session', $this->cookies['bolt_session']);
        $I->amOnPage('bolt/files/config/extensions');

        $I->see('tester-events.bolt.yml', Locator::href("/bolt/file/edit/config/extensions/tester-events.bolt.yml"));
        $I->click('tester-events.bolt.yml', Locator::href("/bolt/file/edit/config/extensions/tester-events.bolt.yml"));

        $I->see('# Sit back and breathe', 'textarea');
        $I->see('its_nice_to_know_you_work_alone: true', 'textarea');

        // Edit the field
        $twig = $I->grabTextFrom('#form_contents', 'textarea');
        $twig .= PHP_EOL . "# Let's make this perfectly clear" ;
        $twig .= PHP_EOL . 'theres_no_secrets_this_year: true' . PHP_EOL;

        $I->fillField('#form_contents', $twig);
        $I->click('#saveeditfile');
        $I->see("File 'extensions/tester-events.bolt.yml' has been saved.");

        $I->see("# Let's make this perfectly clear", 'textarea');
        $I->see('theres_no_secrets_this_year: true', 'textarea');
    }
}
