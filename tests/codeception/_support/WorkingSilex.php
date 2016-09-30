<?php

namespace Codeception\Module;

use Codeception\TestInterface;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpKernel\Client;

/**
 * Change client to follow redirects and keep cookie jar between tests.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class WorkingSilex extends Silex
{
    /** @var CookieJar */
    protected $cookieJar;

    public function _initialize()
    {
        $this->cookieJar = new CookieJar();

        parent::_initialize();
    }

    public function _before(TestInterface $test)
    {
        $this->loadApp();
        $this->client = new Client($this->app, [], null, $this->cookieJar);
        $this->client->followRedirects();
    }
}
