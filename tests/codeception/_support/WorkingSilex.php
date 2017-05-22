<?php

namespace Codeception\Module;

use Codeception\TestInterface;
use Swift_Transport_SpoolTransport as SpoolTransport;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\KernelEvents;

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
        $this->reloadApp();
    }

    protected function loadApp()
    {
        parent::loadApp();

        $this->app->before(function () {
            // Stop the profiler listener from running, it is not needed for tests
            $this->app['dispatcher']->addListener(KernelEvents::TERMINATE, function (Event $event) {
                $event->stopPropagation();
            }, -1023); // Just before \Symfony\Component\HttpKernel\EventListener\ProfilerListener
        });

        $this->app->finish(function () {
            if ($this->app['mailer.initialized'] && $this->app['swiftmailer.use_spool'] && $this->app['swiftmailer.spooltransport'] instanceof SpoolTransport) {
                $spool = $this->app['swiftmailer.spooltransport']->getSpool();
                $r = new \ReflectionClass($spool);
                $p = $r->getProperty('messages');
                $p->setAccessible(true);
                $p->setValue($spool, []);
            }
        }, 512);
    }

    public function reloadApp()
    {
        $this->loadApp();
        $this->client = new Client($this->app, [], null, $this->cookieJar);
        $this->client->followRedirects();
    }

    protected function clientRequest($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $changeHistory = true)
    {
        $this->reloadApp();

        return parent::clientRequest($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }
}
