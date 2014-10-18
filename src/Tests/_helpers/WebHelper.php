<?php
namespace Codeception\Module;

// here you can define custom functions for WebGuy

class WebHelper extends \Codeception\Module
{
    /**
     * Makes sure that WebGuy is logged in.
     * @param array $user An associative array containing keys 'username'
     * and 'password'.
     */
    public function loginAs($user)
    {
        $web = $this->getModule('PhpBrowser');
        $web->amOnPage('/bolt/login');
        $web->fillField('username', $user['username']);
        $web->fillField('password', $user['password']);
        $web->click('Log on');
    }
}
