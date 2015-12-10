<?php

namespace Bolt\Composer\Satis;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Silex\Application;

class StatService
{
    public $urls = ['install' => 'stat/install/%s/%s'];
    /** @var ClientInterface  */
    private $client;
    /** @var LoggerInterface  */
    private $loggerSystem;

    /**
     * StatService constructor.
     *
     * @param ClientInterface $client
     * @param LoggerInterface $loggerSystem
     */
    public function __construct(ClientInterface $client, LoggerInterface $loggerSystem)
    {
        $this->client = $client;
        $this->loggerSystem = $loggerSystem;
    }

    /**
     * Record an extension install.
     *
     * @param string $package
     * @param string $version
     */
    public function recordInstall($package, $version)
    {
        $url = sprintf($this->app['extend.site'] . $this->urls['install'], $package, $version);

        try {
            $this->client->head($url);
        } catch (RequestException $e) {
            $this->loggerSystem->critical($e->getMessage(), ['event' => 'exception', 'exception' => $e]);
        }
    }
}
