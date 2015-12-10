<?php

namespace Bolt\Composer\Satis;

use Bolt\Exception\SatisQueryException;
use GuzzleHttp\Client;

/**
 * Class to provide querying of the Bolt Extensions repo as a
 * service.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class QueryService
{
    public $site;
    public $urls;
    public $format = 'json';

    /** @var \GuzzleHttp\Client */
    private $client;
    /** @var boolean */
    private $isRetry;

    /**
     * Constructor function.
     *
     * @param \GuzzleHttp\Client $client
     * @param string             $site
     * @param array              $urls
     */
    public function __construct(Client $client, $site, $urls = [])
    {
        $this->client = $client;
        $this->site   = $site;
        $this->urls   = $urls;
    }

    public function all()
    {
        $url = $this->urls['list'];

        return $this->execute($url);
    }

    /**
     * Make an extension package info request.
     *
     * @param string $package Composer package name 'author/extension'
     * @param string $bolt    Bolt version number
     *
     * @return string|boolean|object
     */
    public function info($package, $bolt)
    {
        $url = $this->urls['info'];
        $params = ['package' => $package, 'bolt' => $bolt];

        return $this->execute($url, $params);
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Execute the query.
     *
     * @param string $url
     * @param array  $params
     *
     * @throws SatisQueryException
     *
     * @return string|boolean|object
     */
    public function execute($url, $params = [])
    {
        $uri = rtrim(rtrim($this->site, '/') . '/' . ltrim($url, '/') . '?' . http_build_query($params), '?');

        try {
            $result = $this->client->get($uri, ['timeout' => 10])->getBody(true);

            return ($this->format === 'json') ? json_decode($result) : (string) $result;
        } catch (\Exception $e) {
            if ($this->isRetry) {
                $msg = "Error connecting to the Extension Marketplace:\n" . $e->getMessage();
                throw new SatisQueryException($msg, $e->getCode(), $e);
            }
            $this->isRetry = true;
            $this->site = str_replace('https://', 'http://', $this->site);

            return $this->execute($url, $params);
        }
    }
}
