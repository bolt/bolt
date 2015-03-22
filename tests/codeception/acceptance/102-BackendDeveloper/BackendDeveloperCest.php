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
     * Test the file management interface
     *
     * @param \AcceptanceTester $I
     */
    public function fileManagementUploadedFilesTest(\AcceptanceTester $I)
    {
        $I->wantTo("Use the file management 'uploaded files' interface as the 'developer' user");
        $I->loginAs($this->user['developer']);

        $I->amOnPage('bolt/files');

        // $soft has the string with soft hyphens U+00AD
        $soft = 'a­g­r­i­c­u­l­t­u­r­e­-­c­e­r­e­a­l­s­-­f­i­e­l­d­-­6­2­1­.j­p­g';
        $file = 'agriculture-cereals-field-621.jpg';

        $I->see('Create folder', Locator::find('a', ['href' => '#']));
        $I->see($soft, Locator::href("/thumbs/1000x1000r/$file"));

        $I->see('66.23 KiB', 'td');
        $I->see('800 × 533 px', 'td ');
        $I->see('Place on stack',  Locator::find('a', ['href' => '#']));
        $I->see("Rename $file",    Locator::find('a', ['href' => '#']));
        $I->see("Delete $file",    Locator::find('a', ['href' => '#']));
        $I->see("Duplicate $file", Locator::find('a', ['href' => '#']));
    }
}
