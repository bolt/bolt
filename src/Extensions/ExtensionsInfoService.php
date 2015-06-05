<?php
namespace Bolt\Extensions;

use GuzzleHttp\Client;

/**
 * Class to provide querying of the Bolt Extensions repo as a
 * service.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class ExtensionsInfoService
{
    public $site;
    public $urls;
    public $format = 'json';

    /** @var \GuzzleHttp\Client|\Guzzle\Service\Client */
    private $client;
    /** @var boolean */
    private $isRetry;
    /** @var boolean */
    private $deprecated;

    /**
     * Constructor function.
     *
     * @param \GuzzleHttp\Client|\Guzzle\Service\Client $client
     * @param string                                    $site
     * @param array                                     $urls
     * @param boolean                                   $deprecated
     */
    public function __construct($client, $site, $urls = array(), $deprecated = false)
    {
        /** @deprecated remove when PHP 5.3 support is dropped */
        $this->deprecated = $deprecated;
        if (!($client instanceof \GuzzleHttp\Client || $client instanceof \Guzzle\Service\Client)) {
            throw new \InvalidArgumentException(sprintf(
                'First argument passed to %s must be an instance of GuzzleHttp\Client or Guzzle\Service\Client, instance of %s given.',
                __CLASS__,
                get_class($client)
            ));
        }

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
     * @return string|boolean
     */
    public function info($package, $bolt)
    {
        $url = $this->urls['info'];
        $params = array('package' => $package, 'bolt' => $bolt);

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
     * @return string|boolean
     */
    public function execute($url, $params = array())
    {
        $uri = rtrim(rtrim($this->site, '/') . '/' . ltrim($url, '/') . '?' . http_build_query($params), '?');

        try {
            if ($this->deprecated) {
                /** @deprecated remove when PHP 5.3 support is dropped */
                $result = $this->client->get($uri, array('timeout' => 10))->send()->getBody(true);
            } else {
                $result = $this->client->get($uri, array('timeout' => 10))->getBody(true);
            }

            return ($this->format === 'json') ? json_decode($result) : (string) $result;
        } catch (\Exception $e) {
            if ($this->isRetry) {
                return false;
            }
            $this->isRetry = true;
            $this->site = str_replace('https://', 'http://', $this->site);

            return $this->execute($url, $params);
        }
    }
}
