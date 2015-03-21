<?php

use Codeception\Util\Fixtures;

/**
 * Backend 'admin' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAdminCest
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
     * Login the admin user
     *
     * @param \AcceptanceTester $I
     */
    public function loginAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log into the backend as Admin');

        $I->loginAs($this->user['admin']);
        $I->see('Dashboard');
        $I->see("You've been logged on successfully.");
    }

    /**
     * Create a 'editor' user with the 'editor' role
     *
     * @param \AcceptanceTester $I
     */
    public function createEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Create a 'editor' user");

        $I->loginAs($this->user['admin']);
        $I->click('Users');
        $I->click('Add a new user');
        $I->see('Create a new user');

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

        $I->loginAs($this->user['admin']);
        $I->click('Users');
        $I->click('Add a new user');
        $I->see('Create a new user');

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

        $I->loginAs($this->user['admin']);
        $I->click('Users');
        $I->click('Add a new user');
        $I->see('Create a new user');

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
     * Check that admin user can view all content types
     *
     * @param \AcceptanceTester $I
     */
    public function viewAllContenttypesTest(\AcceptanceTester $I)
    {
        $I->wantTo('make sure the admin user can view all content types');
        $I->loginAs($this->user['admin']);
        $I->click('Dashboard');

        $I->see('Page');
        $I->see('Entries');
        $I->see('Showcases');
    }

    /**
     * Edit site permissions
     *
     * @param \AcceptanceTester $I
     */
    public function editPermissionsTest(\AcceptanceTester $I)
    {
        $I->wantTo('edit permissions.yml and restrict access to certain Contenttypes');
        $I->loginAs($this->user['admin']);

        $I->amOnPage('bolt/file/edit/config/permissions.yml');
        $perms = $I->getUpdatedPermissions();
        $I->fillField('#form_contents', $perms);
        $I->click('Save');
        $I->see("File 'permissions.yml' has been saved.");
        $I->see('change-ownership: [ ]');
    }

    /**
     * Logout the admin user
     *
     * @param \AcceptanceTester $I
     */
    public function logoutAdminUserTest(\AcceptanceTester $I)
    {
        $I->wantTo('log out of the backend as Admin');

        $I->amOnPage('bolt');
        $I->loginAs($this->user['admin']);

        $I->see('Dashboard');
        $I->click('Logout');

        $I->see('You have been logged out');

        $I->amOnPage('bolt');
        $I->see('Please log on');
    }
}
