<?php

namespace Bolt\Storage;

use GuzzleHttp\Client;

/**
 * Handles Fetching Prefill Content from an API service.
 */
class Prefill
{
    protected $client;

    /**
     * Constructor function.
     *
     * @param \GuzzleHttp\Client|\Guzzle\Service\Client $client
     */
    public function __construct($client, $deprecated = false)
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
    }

    /**
     * Fetches the content from the service.
     *
     * @param string $request Parameters to add to the base uri - eg /medium/decorate/ol
     * @param string $base
     *
     * @return string
     */
    public function get($request, $base = 'http://loripsum.net/api/')
    {
        $uri = $base . ltrim($request, '/');

        if ($this->deprecated) {
            /** @deprecated remove when PHP 5.3 support is dropped */
            return $this->client->get($uri, array('timeout' => 10))->send()->getBody(true);
        } else {
            return $this->client->get($uri, array('timeout' => 10))->getBody(true);
        }
    }
}
