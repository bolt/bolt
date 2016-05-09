<?php

use Codeception\Util\Fixtures;

abstract class AbstractAcceptanceTest
{
    /** @var string[] */
    protected $user;
    /** @var string[] */
    protected $tokenNames;
    /** @var string[] */
    protected $cookies = [];

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
     * @param AcceptanceTester $I
     */
    protected function saveLogin(\AcceptanceTester $I)
    {
        $authTokenName = (string) $this->tokenNames['authtoken'];
        $sessionTokenName = (string) $this->tokenNames['session'];

        $this->cookies[$authTokenName] = $I->grabCookie($this->tokenNames['authtoken']);
        $this->cookies[$sessionTokenName] = $I->grabCookie($this->tokenNames['session']);
    }

    /**
     * @param AcceptanceTester $I
     */
    protected function setLoginCookies(\AcceptanceTester $I)
    {
        $authTokenName = (string) $this->tokenNames['authtoken'];
        $sessionTokenName = (string) $this->tokenNames['session'];

        $I->setCookie($this->tokenNames['authtoken'], $this->cookies[$authTokenName]);
        $I->setCookie($this->tokenNames['session'], $this->cookies[$sessionTokenName]);
    }
}
