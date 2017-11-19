<?php

namespace Codeception\Module;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Lib\InnerBrowser;

class AcceptanceHelper extends \Codeception\Module
{
    /**
     * Makes sure that WebTester is logged in.
     *
     * @param array $user an associative array containing keys 'username' and 'password'
     */
    public function loginAs($user)
    {
        $this->doLogin($user['username'], $user['password']);
    }

    /**
     * Makes sure that WebTester is logged in using email.
     *
     * @param array $user an associative array containing keys 'username' and 'password'
     */
    public function loginWithEmailAs($user)
    {
        $this->doLogin($user['email'], $user['password']);
    }

    protected function doLogin($usernameOrEmail, $password)
    {
        /** @var InnerBrowser $web */
        $web = $this->moduleContainer->moduleForAction('amOnPage');

        // Clear cookies so bolt let's us login as someone else.
        $web->client->getCookieJar()->clear();

        $web->amOnPage('/bolt/login');
        $web->fillField(['id' => 'user_login_username'], $usernameOrEmail);
        $web->fillField(['id' => 'user_login_password'], $password);
        $web->click(['id' => 'user_login_login']);
    }

    /**
     * Reload app so configuration changes take affect.
     */
    public function reloadApp()
    {
        /** @var WorkingSilex $silex */
        $silex = $this->moduleContainer->getModule('WorkingSilex');
        $silex->reloadApp();
    }
}
