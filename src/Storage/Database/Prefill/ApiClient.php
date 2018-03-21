<?php

namespace Bolt\Storage\Database\Prefill;

use GuzzleHttp\Client;

/**
 * Handles fetching prefill text content from an API service.
 */
class ApiClient
{
    /** @var Client */
    protected $client;

    /**
     * Constructor function.
     *
     * @param \GuzzleHttp\Client $client
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

        return $this->client->get($uri, ['timeout' => 10])->getBody();
    }
}
