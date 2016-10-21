<?php

use Codeception\Util\Fixtures;

abstract class AbstractAcceptanceTest
{
    /** @var string[] */
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
     * @deprecated
     *
     * @param AcceptanceTester $I
     */
    protected function saveLogin(\AcceptanceTester $I)
    {
    }

    /**
     * @deprecated
     *
     * @param AcceptanceTester $I
     */
    protected function setLoginCookies(\AcceptanceTester $I)
    {
    }
}
