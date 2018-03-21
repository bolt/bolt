<?php

namespace Bolt\Tests\Mocks;

use Bolt\Storage\Database\Prefill\ImageClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class ImageApiMock extends ImageClient
{
    public function __construct()
    {
    }

    public function get($uri)
    {
        throw new RequestException('', new Request('GET', $uri));
    }
}
