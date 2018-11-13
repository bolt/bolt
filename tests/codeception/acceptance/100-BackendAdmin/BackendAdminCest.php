<?php

use Codeception\Util\Locator;

/**
 * Backend 'admin' tests.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAdminCest extends AbstractAcceptanceTest
{
    /**
     * Login the admin user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log into the backend as Admin');

        $I->loginAs($this->user['admin']);
        $this->saveLogin($I);

        $I->see('Dashboard');
        $I->see('Configuration', Locator::href('/bolt/users'));
        $I->see("You've been logged on successfully.");
    }

    /**
     * Create a 'author' user with the 'author' role.
     *
     * @param \AcceptanceTester $I
     */
    public function createAuthorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'author' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('user_edit[username]',         $this->user['author']['username']);
        $I->fillField('user_edit[password][first]',  $this->user['author']['password']);
        $I->fillField('user_edit[password][second]', $this->user['author']['password']);
        $I->fillField('user_edit[email]',            $this->user['author']['email']);
        $I->fillField('user_edit[displayname]',      $this->user['author']['displayname']);
        $I->selectOption('user_edit[enabled]',       1);

        // Add the "editor" role
        $I->checkOption('#user_edit_roles_1');

        // Submit
        $I->click('#user_edit button[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['author']['displayname']} has been saved");
    }

    /**
     * Create a 'editor' user with the 'editor' role.
     *
     * @param \AcceptanceTester $I
     */
    public function createEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create an 'editor' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('user_edit[username]',         $this->user['editor']['username']);
        $I->fillField('user_edit[password][first]',  $this->user['editor']['password']);
        $I->fillField('user_edit[password][second]', $this->user['editor']['password']);
        $I->fillField('user_edit[email]',            $this->user['editor']['email']);
        $I->fillField('user_edit[displayname]',      $this->user['editor']['displayname']);
        $I->selectOption('user_edit[enabled]',       1);

        // Add the "editor" role
        $I->checkOption('#user_edit_roles_0');

        // Submit
        $I->click('#user_edit button[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['editor']['displayname']} has been saved");
    }

    /**
     * Create a 'manager' user with the 'chief-editor' role.
     *
     * @param \AcceptanceTester $I
     */
    public function createManagerTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'manager' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('user_edit[username]',         $this->user['manager']['username']);
        $I->fillField('user_edit[password][first]',  $this->user['manager']['password']);
        $I->fillField('user_edit[password][second]', $this->user['manager']['password']);
        $I->fillField('user_edit[email]',            $this->user['manager']['email']);
        $I->fillField('user_edit[displayname]',      $this->user['manager']['displayname']);
        $I->selectOption('user_edit[enabled]',       1);

        // Add the "chief-editor" role
        $I->checkOption('#user_edit_roles_1');

        // Submit
        $I->click('#user_edit button[type=submit]');

        // Save is successful?
        $I->see("User {$this->user['manager']['displayname']} has been saved");
    }

    /**
     * Create a 'developer' user with the 'developer' role.
     *
     * @param \AcceptanceTester $I
     */
    public function createDeveloperTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'developer' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('user_edit[username]',         $this->user['developer']['username']);
        $I->fillField('user_edit[password][first]',  $this->user['developer']['password']);
        $I->fillField('user_edit[password][second]', $this->user['developer']['password']);
        $I->fillField('user_edit[email]',            $this->user['developer']['email']);
        $I->fillField('user_edit[displayname]',      $this->user['developer']['displayname']);
        $I->selectOption('user_edit[enabled]',       1);

        // Add the "developer" role
        $I->checkOption('#user_edit_roles_3');

        // Submit
        $I->click('#user_edit button[type=submit]');

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
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('user_edit[username]',         $this->user['lemmings']['username']);
        $I->fillField('user_edit[password][first]',  $this->user['lemmings']['password']);
        $I->fillField('user_edit[password][second]', $this->user['lemmings']['password']);
        $I->fillField('user_edit[email]',            $this->user['lemmings']['email']);
        $I->fillField('user_edit[displayname]',      $this->user['lemmings']['displayname']);
        $I->selectOption('user_edit[enabled]',       1);

        // Add the "admin" role
        $I->checkOption('#user_edit_roles_2');

        // Submit
        $I->click('#user_edit button[type=submit]');

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
        $I->wantTo('Fail creating a user where password matches user and display names and email address is invalid.');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/users');

        $I->click('Add a new user', Locator::href('/bolt/users/edit/'));
        $I->see('Create a new user account');

        // Fill in form
        $I->fillField('user_edit[username]',         'derpaderp');
        $I->fillField('user_edit[password][first]',  'DerpADerp');
        $I->fillField('user_edit[password][second]', 'DerpADerp');
        $I->fillField('user_edit[email]',            'derpaderp');
        $I->fillField('user_edit[displayname]',      'Derpy Derpaderp');
        $I->selectOption('user_edit[enabled]',       1);

        // Add the "admin" role
        $I->checkOption('#user_edit_roles_2');

        // Submit
        $I->click('#user_edit button[type=submit]');

        // Save is *not* successful?
        $I->see('Password must not match the username.');
        $I->see('Password must not be a part of the display name.');
        $I->see('This email address is not valid');
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
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/config/config.yml');

        $yaml = $I->getUpdatedConfig();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');

        $I->amOnPage('/bolt/file/edit/config/config.yml');
        $I->see('notfound: resources/not-found');
        $I->see('canonical: example.org');
        $I->see("changelog:\n    enabled: true");
    }

    /**
     * Edit theme's theme.yml and set-up TemplateFields.
     *
     * @param \AcceptanceTester $I
     */
    public function editThemeConfigTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit theme.yml and add TemplateFields configuration');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/themes/base-2018/theme.yml');

        $yaml = $I->getUpdatedTheme();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');

        $indent = '            ';
        $I->amOnPage('/bolt/file/edit/themes/base-2018/theme.yml');
        $I->see('page.twig:');
        $I->see("text:\n{$indent}type: text");
        $I->see("html:\n{$indent}type: html");
        $I->see("textarea:\n{$indent}type: textarea");
        $I->see("markdown:\n{$indent}type: markdown");
        $I->see("geolocation:\n{$indent}type: geolocation");
        $I->see("video:\n{$indent}type: video");
        $I->see("image:\n{$indent}type: image");
        $I->see("file:\n{$indent}type: file");
        $I->see("filelist:\n{$indent}type: filelist");
        $I->see("checkbox:\n{$indent}type: checkbox");
        /**
         * Disabled as currently unsupported due to bug in persistence
         */
        //$I->see("date:\n{$indent}type: date");
        //$I->see("datetime:\n{$indent}type: datetime");
        $I->see("integer:\n{$indent}type: integer");
        $I->see("float:\n{$indent}type: float");
        $I->see("select_map:\n{$indent}type: select");
        $I->see("select_list:\n{$indent}type: select");
        $I->see("select_multi:\n{$indent}type: select");
        /**
         * Disabled as currently unsupported due to problems in extension
         * fields, and in test due to |first filter in base-2016:
         *
         * @see https://github.com/bolt/bolt/blob/v3.2.16/theme/base-2016/partials/_sub_fields.twig#L104
         */
        //$I->see("repeater:\n{$indent}type: repeater");
    }

    /**
     * Edit contenttypes.yml and add a 'Resources' ContentType
     *
     * @param \AcceptanceTester $I
     */
    public function addNewContentTypeTest(\AcceptanceTester $I)
    {
        $I->wantTo("edit contenttypes.yml and add a 'Resources' ContentType");
        $I->loginAs($this->user['admin']);

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/config/contenttypes.yml');

        $yaml = $I->getUpdatedContentTypes();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');
        $I->amOnPage('/bolt/file/edit/config/contenttypes.yml');
        $I->see('name: Resources');
        $I->see('singular_name: Resource');
        $I->see('viewless: true');
    }

    /**
     * Update the database after creating the Resources ContentType.
     *
     * @param \AcceptanceTester $I
     */
    public function updateDatabaseTest(\AcceptanceTester $I)
    {
        $I->wantTo("update the database and add the new 'Resources' ContentType");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/clearcache');
        $I->amOnPage('/bolt/dbcheck');
        $I->see('The database needs to be updated/repaired');

        $I->see('Check Database', 'a');
        $I->click('Check Database', 'a');

        // We are now on '/bolt/dbcheck'.
        $I->see('is not present');
        $I->see('Update the database', Locator::find('button', ['type' => 'submit']));

        $I->click('Update the database', Locator::find('button', ['type' => 'submit']));
        $I->see('Modifications made to the database');
        $I->see('Created table');
        $I->see('Your database is now up to date');
    }

    /**
     * Update the database after creating the Resources ContentType.
     *
     * @param \AcceptanceTester $I
     */
    public function addNotFoundRecordTest(\AcceptanceTester $I)
    {
        $I->wantTo("create a 404 'not-found' record");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/editcontent/resources');

        $I->see('New Resource', 'h1');

        $body = file_get_contents(CODECEPTION_DATA . '/not-found.body.html');

        $I->fillField('#title', '404 reasons to cry');
        $I->fillField('#slug',  'not-found');
        $I->fillField('#body',  $body);

        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save' => 1]]);

        $I->see('Well, this is kind of embarrassing!');
        $I->see('You have what we call in the business, a 404.');
        $I->see('The new Resource has been saved.');
    }

    /**
     * Check that admin user can view all ContentTypes.
     *
     * @param \AcceptanceTester $I
     */
    public function viewAllContentTypesTest(\AcceptanceTester $I)
    {
        $I->wantTo('make sure the admin user can view all content types');

        // Set up the browser
        $this->setLoginCookies($I);
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
     * Check that admin user can create an empty showcase.
     *
     * @param \AcceptanceTester $I
     */
    public function canCreateEmptyShowcaseTest(\AcceptanceTester $I)
    {
        $I->wantTo('check that admin user can create an empty showcase');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('New Showcase');
        $I->click('New Showcase');

        $I->fillField('#title', 'A Strange Drop Bear');
        $I->submitForm('form[name="content_edit"]', ['content_edit' => ['save' => 1]]);

        $I->see('The new Showcase has been saved.');
        $I->seeLink('A Strange Drop Bear', '/bolt/editcontent/showcases/1');
    }

    /**
     * Edit site permissions.
     *
     * @param \AcceptanceTester $I
     */
    public function editPermissionsTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit permissions.yml and restrict access to certain ContentTypes');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/config/permissions.yml');

        $yaml = $I->getUpdatedPermissions();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');

        $I->amOnPage('/bolt/file/edit/config/permissions.yml');
        $I->see('change-ownership: [ ]');
    }

    /**
     * Edit the taxonomy.
     *
     * @param \AcceptanceTester $I
     */
    public function editTaxonomyTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit taxonomy.yml and reorder category options');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/config/taxonomy.yml');

        $yaml = $I->getUpdatedTaxonomy();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');

        $I->amOnPage('/bolt/file/edit/config/taxonomy.yml');
        $I->see('options: [books, events, fun, life, love, movies, music, news]');
    }

    /**
     * Edit the menu file.
     *
     * @param \AcceptanceTester $I
     */
    public function editMenuTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit menu.yml and reorder category options');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/config/menu.yml');

        $yaml = $I->getUpdatedMenu();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');

        $I->amOnPage('/bolt/file/edit/config/menu.yml');
        $I->see('Showcases Listing');
        $I->see('path: showcases/');
    }

    /**
     * Edit the routing file.
     *
     * @param \AcceptanceTester $I
     */
    public function editRoutingTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit routing.yml and add a pagebinding route');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/file/edit/config/routing.yml');

        $yaml = $I->getUpdatedRouting();
        $I->fillField('#file_edit_contents', $yaml);
        $I->click('Save', '#file_edit_save');

        $I->amOnPage('/bolt/file/edit/config/routing.yml');
        $I->see('pagebinding:');
        $I->see('/{slug}');
        $I->see('contenttype: pages');
    }

    /**
     * Check the we can use the system log.
     *
     * @param \AcceptanceTester $I
     */
    public function checkSystemLogTest(\AcceptanceTester $I)
    {
        $I->wantTo('use the system log interface.');

        // Set up the browser
        $this->setLoginCookies($I);
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
     * Check the we can use the change log.
     *
     * @param \AcceptanceTester $I
     */
    public function checkChangeLogTest(\AcceptanceTester $I)
    {
        $I->wantTo('use the change log interface.');

        // Set up the browser
        $this->setLoginCookies($I);
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
     * Clear the cache.
     *
     * @param \AcceptanceTester $I
     */
    public function clearCacheTest(\AcceptanceTester $I)
    {
        $I->wantTo('flush the cache.');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/clearcache');

        $I->see('Cleared cache');
        $I->see('Clear cache again', 'a');
    }

    /**
     * Logout the admin user.
     *
     * @param \AcceptanceTester $I
     */
    public function logoutAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log out of the backend as Admin');

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('Dashboard');
        $I->click('Logout');

        $I->amOnPage('/bolt');

        $I->seeInCurrentUrl('/bolt/login');
    }
}
