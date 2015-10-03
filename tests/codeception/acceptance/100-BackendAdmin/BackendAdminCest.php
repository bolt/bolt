<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Locator;

/**
 * Backend 'admin' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAdminCest
{
    /** @var array */
    protected $user;
    /** @var array */
    protected $tokenNames;

    /** @var array */
    private $cookies = [];

    /**
     * @param \AcceptanceTester $I
     */
    public function _before(\AcceptanceTester $I)
    {
        $this->user = Fixtures::get('users');
        $this->tokenNames = Fixtures::get('tokenNames');
    }

    /**
     * @param \AcceptanceTester $I
     */
    public function _after(\AcceptanceTester $I)
    {
    }

    /**
     * Login the admin user
     *
     * @param \AcceptanceTester $I
     */
    public function loginAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log into the backend as Admin');

        $I->loginAs($this->user['admin']);
        $this->cookies[$this->tokenNames['authtoken']] = $I->grabCookie($this->tokenNames['authtoken']);
        $this->cookies[$this->tokenNames['session']] = $I->grabCookie($this->tokenNames['session']);

        $I->see('Dashboard');
        $I->see('Configuration', Locator::href('/bolt/users'));
        $I->see("You've been logged on successfully.");
    }

    /**
     * Create a 'author' user with the 'author' role
     *
     * @param \AcceptanceTester $I
     */
    public function createAuthorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'author' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('form[username]',              $this->user['author']['username']);
        $I->fillField('form[password]',              $this->user['author']['password']);
        $I->fillField('form[password_confirmation]', $this->user['author']['password']);
        $I->fillField('form[email]',                 $this->user['author']['email']);
        $I->fillField('form[displayname]',           $this->user['author']['displayname']);

        // Add the "editor" role
        $I->checkOption('#form_roles_1');

        // Submit
        $I->click('input[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['author']['displayname']} has been saved");
    }

    /**
     * Create a 'editor' user with the 'editor' role
     *
     * @param \AcceptanceTester $I
     */
    public function createEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create an 'editor' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('form[username]',              $this->user['editor']['username']);
        $I->fillField('form[password]',              $this->user['editor']['password']);
        $I->fillField('form[password_confirmation]', $this->user['editor']['password']);
        $I->fillField('form[email]',                 $this->user['editor']['email']);
        $I->fillField('form[displayname]',           $this->user['editor']['displayname']);

        // Add the "editor" role
        $I->checkOption('#form_roles_0');

        // Submit
        $I->click('input[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['editor']['displayname']} has been saved");
    }

    /**
     * Create a 'manager' user with the 'chief-editor' role
     *
     * @param \AcceptanceTester $I
     */
    public function createManagerTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'manager' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('form[username]',              $this->user['manager']['username']);
        $I->fillField('form[password]',              $this->user['manager']['password']);
        $I->fillField('form[password_confirmation]', $this->user['manager']['password']);
        $I->fillField('form[email]',                 $this->user['manager']['email']);
        $I->fillField('form[displayname]',           $this->user['manager']['displayname']);

        // Add the "chief-editor" role
        $I->checkOption('#form_roles_1');

        // Submit
        $I->click('input[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['manager']['displayname']} has been saved");
    }

    /**
     * Create a 'developer' user with the 'developer' role
     *
     * @param \AcceptanceTester $I
     */
    public function createDeveloperTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'developer' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('form[username]',              $this->user['developer']['username']);
        $I->fillField('form[password]',              $this->user['developer']['password']);
        $I->fillField('form[password_confirmation]', $this->user['developer']['password']);
        $I->fillField('form[email]',                 $this->user['developer']['email']);
        $I->fillField('form[displayname]',           $this->user['developer']['displayname']);

        // Add the "developer" role
        $I->checkOption('#form_roles_3');

        // Submit
        $I->click('input[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['developer']['displayname']} has been saved");
    }

    /**
     * Create a 'Lemmings' user with the 'admin' role… until they cliff appears!
     *
     * @param \AcceptanceTester $I
     */
    public function createLemmingsTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'lemmings' user");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('form[username]',              $this->user['lemmings']['username']);
        $I->fillField('form[password]',              $this->user['lemmings']['password']);
        $I->fillField('form[password_confirmation]', $this->user['lemmings']['password']);
        $I->fillField('form[email]',                 $this->user['lemmings']['email']);
        $I->fillField('form[displayname]',           $this->user['lemmings']['displayname']);

        // Add the "admin" role
        $I->checkOption('#form_roles_2');

        // Submit
        $I->click('input[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['lemmings']['displayname']} has been saved");
    }

    /**
     * Fail creating a user where password matches user and display names and email address is invalid.
     *
     * @param \AcceptanceTester $I
     */
    public function createDerpaderpTest(\AcceptanceTester $I)
    {
        $I->wantTo("Fail creating a user where password matches user and display names and email address is invalid.");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('form[username]',              'derpaderp');
        $I->fillField('form[password]',              'DerpADerp');
        $I->fillField('form[password_confirmation]', 'DerpADerp');
        $I->fillField('form[email]',                 'derpaderp');
        $I->fillField('form[displayname]',           'Derpy Derpaderp');

        // Add the "admin" role
        $I->checkOption('#form_roles_2');

        // Submit
        $I->click('input[type=submit]');

        // Save is *not* successful?
        $I->see('Password must not match the username.');
        $I->see('Password must not be a part of the display name.');
        $I->see('This value is not a valid email address.');
    }

    /**
     * Edit site config and set 'canonical', 'notfound' and 'changelog'.
     *
     * @param \AcceptanceTester $I
     */
    public function editConfigTest(\AcceptanceTester $I)
    {
        $I->wantTo("edit config.yml and set 'canonical', 'notfound' and 'changelog'");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/config.yml');

        $yaml = $I->getUpdatedConfig();
        $I->fillField('#form_contents', $yaml);
        $I->click('Save', '#saveeditfile');

        $I->amOnPage('/bolt/file/edit/config/config.yml');
        $I->see('notfound: resources/not-found');
        $I->see('canonical: example.org');
        $I->see("changelog:\n    enabled: true");
    }

    /**
     * Edit contenttypes.yml and add a 'Resources' Contenttype
     *
     * @param \AcceptanceTester $I
     */
    public function addNewContentTypeTest(\AcceptanceTester $I)
    {
        $I->wantTo("edit contenttypes.yml and add a 'Resources' Contenttype");
        $I->loginAs($this->user['admin']);

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/contenttypes.yml');

        $yaml = $I->getUpdatedContenttypes();
        $I->fillField('#form_contents', $yaml);
        $I->click('Save');
        $I->amOnPage('/bolt/file/edit/config/contenttypes.yml');
        $I->see('name: Resources');
        $I->see('singular_name: Resource');
        $I->see('viewless: true');
    }

    /**
     * Update the database after creating the Resources Contenttype
     *
     * @param \AcceptanceTester $I
     */
    public function updateDatabaseTest(\AcceptanceTester $I)
    {
        $I->wantTo("update the database and add the new 'Resources' Contenttype");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/dbcheck');

        $I->see('The database needs to be updated/repaired');
        $I->see('is not present');
        $I->see('Update the database', Locator::find('button', ['type' => 'submit']));

        $I->click('Update the database', Locator::find('button', ['type' => 'submit']));
        $I->see('Modifications made to the database');
        $I->see('Created table');
        $I->see('Your database is now up to date');
    }

    /**
     * Update the database after creating the Resources Contenttype
     *
     * @param \AcceptanceTester $I
     */
    public function addNotFoundRecordTest(\AcceptanceTester $I)
    {
        $I->wantTo("create a 404 'not-found' record");

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/editcontent/resources');

        $I->see('New Resource', 'h1');

        $body = file_get_contents(CODECEPTION_DATA . '/not-found.body.html');

        $I->fillField('#title', '404');
        $I->fillField('#slug',  'not-found');
        $I->fillField('#body',  $body);

        $I->click('Save Resource', '#savecontinuebutton');

        $I->see('Well, this is kind of embarrassing!');
        $I->see('You have what we call in the business, a 404.');
        $I->see('The new Resource has been saved.');
    }

    /**
     * Check that admin user can view all content types
     *
     * @param \AcceptanceTester $I
     */
    public function viewAllContenttypesTest(\AcceptanceTester $I)
    {
        $I->wantTo('make sure the admin user can view all content types');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt');

        // Pages
        $I->see('Pages',      Locator::href('/bolt/overview/pages'));
        $I->see('View Pages', Locator::href('/bolt/overview/pages'));
        $I->see('New Page',   Locator::href('/bolt/editcontent/pages'));

        // Entries
        $I->see('Entries',      Locator::href('/bolt/overview/entries'));
        $I->see('View Entries', Locator::href('/bolt/overview/entries'));
        $I->see('New Entry',    Locator::href('/bolt/editcontent/entries'));

        // Showcases
        $I->see('Showcases',      Locator::href('/bolt/overview/showcases'));
        $I->see('View Showcases', Locator::href('/bolt/overview/showcases'));
        $I->see('New Showcase',   Locator::href('/bolt/editcontent/showcases'));

        // Resources
        $I->see('Resources',      Locator::href('/bolt/overview/resources'));
        $I->see('View Resources', Locator::href('/bolt/overview/resources'));
        $I->see('New Resource',   Locator::href('/bolt/editcontent/resources'));
    }

    /**
     * Edit site permissions
     *
     * @param \AcceptanceTester $I
     */
    public function editPermissionsTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit permissions.yml and restrict access to certain Contenttypes');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/permissions.yml');

        $yaml = $I->getUpdatedPermissions();
        $I->fillField('#form_contents', $yaml);
        $I->click('Save', '#saveeditfile');

        $I->amOnPage('/bolt/file/edit/config/permissions.yml');
        $I->see('change-ownership: [ ]');
    }

    /**
     * Edit the taxonomy
     *
     * @param \AcceptanceTester $I
     */
    public function editTaxonomyTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit taxonomy.yml and reorder category options');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/taxonomy.yml');

        $yaml = $I->getUpdatedTaxonomy();
        $I->fillField('#form_contents', $yaml);
        $I->click('Save', '#saveeditfile');

        $I->amOnPage('/bolt/file/edit/config/taxonomy.yml');
        $I->see('options: [books, events, fun, life, love, movies, music, news]');
    }

    /**
     * Edit the menu file
     *
     * @param \AcceptanceTester $I
     */
    public function editMenuTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit menu.yml and reorder category options');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/menu.yml');

        $yaml = $I->getUpdatedMenu();
        $I->fillField('#form_contents', $yaml);
        $I->click('Save', '#saveeditfile');

        $I->amOnPage('/bolt/file/edit/config/menu.yml');
        $I->see('Showcases Listing');
        $I->see('path: showcases/');
    }

    /**
     * Edit the routing file
     *
     * @param \AcceptanceTester $I
     */
    public function editRoutingTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit routing.yml and add a pagebinding route');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/file/edit/config/routing.yml');

        $yaml = $I->getUpdatedRouting();
        $I->fillField('#form_contents', $yaml);
        $I->click('Save', '#saveeditfile');

        $I->amOnPage('/bolt/file/edit/config/routing.yml');
        $I->see('pagebinding:');
        $I->see("/{slug}");
        $I->see("contenttype: pages");
    }

    /**
     * Check the we can use the system log
     *
     * @param \AcceptanceTester $I
     */
    public function checkSystemLogTest(\AcceptanceTester $I)
    {
        $I->wantTo('use the system log interface.');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/systemlog');

        // Layout
        $I->see('System Log', 'h1');
        $I->see('Trim System Log', 'a');
        $I->see('Clear System Log', 'a');

        // An expect entry
        $I->see('Logged in: admin', 'td');
        $I->see('Using cached data', 'td');

        // Trim
        $I->click('Trim System Log', 'a');
        $I->see('The system log has been trimmed.');
    }

    /**
     * Check the we can use the change log
     *
     * @param \AcceptanceTester $I
     */
    public function checkChangeLogTest(\AcceptanceTester $I)
    {
        $I->wantTo('use the change log interface.');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/changelog');

        // Layout
        $I->see('Change Log', 'h1');
        $I->see('Trim Change Log', 'a');
        $I->see('Clear Change Log', 'a');

        $I->see('Type', 'th');
        $I->see('ContentType', 'th');
        $I->see('ID', 'th');
        $I->see('Title', 'th');
        $I->see('Changed Fields', 'th');
        $I->see("Editor's Comment", 'th');
        $I->see('User', 'th');
        $I->see('Date', 'th');

        // Trim
        $I->click('Trim Change Log', 'a');
        $I->see('The change log has been trimmed.');
    }

    /**
     * Clear the cache
     *
     * @param \AcceptanceTester $I
     */
    public function clearCacheTest(\AcceptanceTester $I)
    {
        $I->wantTo('flush the cache.');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt/clearcache');

        $I->see('Deleted');
        $I->see('files from cache.');
        $I->see('Clear cache again', 'a');
    }

    /**
     * Logout the admin user
     *
     * @param \AcceptanceTester $I
     */
    public function logoutAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log out of the backend as Admin');

        // Set up the browser
        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$this->tokenNames['authtoken']]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$this->tokenNames['session']]);
        $I->amOnPage('/bolt');

        $I->see('Dashboard');
        $I->click('Logout');

        $I->see('You have been logged out');

        $I->amOnPage('/bolt');
        $I->see('Please log on');
    }
}
