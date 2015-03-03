<?php

namespace Bolt\Storage;

use Guzzle\Service\Client;

/**
 * Handles Fetching Prefill Content from an API service.
 */
class Prefill
{
    protected $client;

    /**
     * Constructor function.
     *
     * @param \Guzzle\Service\Client $client
     */
    public function __construct(Client $client)
    {
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

        return $this->client->get($uri, array('timeout' => 10))->send()->getBody(true);
    }
}
