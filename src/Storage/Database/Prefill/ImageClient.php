<?php

namespace Bolt\Storage\Database\Prefill;

use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;

/**
 * Handles fetching prefill images from an API service.
 */
class ImageClient
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
     * Fetches an image from a remote source.
     *
     * @param string $url URL of the remote image to fetch
     *
     * @return StreamInterface
     */
    public function get($url)
    {
        $url = str_replace('__random__', rand(100000, 999999), $url);

        return $this->client->get($url, ['timeout' => 10])->getBody();
    }
}
