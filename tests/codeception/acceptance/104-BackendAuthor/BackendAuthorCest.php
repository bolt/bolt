<?php


/**
 * Backend 'author' tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BackendAuthorCest extends AbstractAcceptanceTest
{
    /**
     * Login as the author user.
     *
     * @param \AcceptanceTester $I
     */
    public function loginEditorTest(\AcceptanceTester $I)
    {
        $I->wantTo("Login as 'author' user");

        $I->loginAs($this->user['author']);
        $this->saveLogin($I);

        $I->see('Dashboard');
    }

    /**
     * Create an 'About' page record.
     *
     * @param \AcceptanceTester $I
     */
    public function editAboutPageTest(\AcceptanceTester $I)
    {
        $I->wantTo("Edit the 'About' page as the 'author' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt');

        $I->see('Edit', 'a');
        $I->click('Edit', 'a');

        $I->click('Save Page', '#savecontinuebutton');

        $I->see('The changes to the Page have been saved.');
    }

    /**
     * Search for the 'About' page record.
     *
     * @param \AcceptanceTester $I
     */
    public function omnisearchTest(\AcceptanceTester $I)
    {
        $I->wantTo("Search for the 'About' page as the 'author' user");

        // Set up the browser
        $this->setLoginCookies($I);
        $I->amOnPage('/bolt/omnisearch');

        $I->fillField('.col-md-8 .form-control', 'About');

        $I->click('Search', '.col-md-8 [type="submit"]');

        $I->seeLink('Edit', '/bolt/editcontent/pages/2');
    }
}
