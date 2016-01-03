<?php

namespace Bolt\Composer\Satis;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class StatService
{
    /** @var array */
    public $urls = ['install' => 'stat/install/%s/%s'];
    /** @var string */
    private $extendSite;
    /** @var ClientInterface  */
    private $client;
    /** @var LoggerInterface  */
    private $loggerSystem;

    /**
     * StatService constructor.
     *
     * @param ClientInterface $client
     * @param LoggerInterface $loggerSystem
     * @param string          $extendSite
     */
    public function __construct(ClientInterface $client, LoggerInterface $loggerSystem, $extendSite)
    {
        $this->client = $client;
        $this->loggerSystem = $loggerSystem;
        $this->extendSite = $extendSite;
    }

    /**
     * Record an extension install.
     *
     * @param string $package
     * @param string $version
     */
    public function recordInstall($package, $version)
    {
        $url = sprintf($this->extendSite . $this->urls['install'], $package, $version);

        try {
            $this->client->head($url);
        } catch (RequestException $e) {
            $this->loggerSystem->critical($e->getMessage(), ['event' => 'exception', 'exception' => $e]);
        }
    }
}
