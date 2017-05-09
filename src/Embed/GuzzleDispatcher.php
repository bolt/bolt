<?php

namespace Bolt\Embed;

use Embed\Http\DispatcherInterface;
use Embed\Http\ImageResponse;
use Embed\Http\Response;
use Embed\Http\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

/**
 * Guzzle dispatcher for the embed/embed service.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GuzzleDispatcher implements DispatcherInterface
{
    /** @var Client */
    protected $client;
    /** @var \Embed\Http\AbstractResponse[] */
    private $responses = [];

    /**
     * Constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Url $url)
    {
        $result = $this->getGuzzleResponse($url);

        return $this->responses[] = new Response(
            $url,
            Url::create($result['url']),
            $result['statusCode'],
            $result['contentType'],
            $result['content'],
            $result['headers'],
            $result['info']
        );
    }

    /**
     * @inheritDoc
     */
    public function dispatchImages(array $urls)
    {
        throw new \RuntimeException(sprintf('%s is not currently implemented', __METHOD__));
    }

    /**
     * Execute a Guzzle request, and return an API response.
     *
     * @param Url   $url
     * @param array $options
     *
     * @return array
     */
    protected function getGuzzleResponse(Url $url, array $options = [])
    {
        if (version_compare(Client::VERSION, '6.0.0', '<')) {
            /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
            $options['allow_redirects'] = [
                'max' => 10,
            ];
            $guzzleResponse = $this->client->get((string) $url, $options);
        } else {
            $allowRedirects = $this->client->getConfig('allow_redirects');
            $allowRedirects['max'] = 10;
            $allowRedirects['track_redirects'] = true;
            $options['allow_redirects'] = $allowRedirects;

            $uri = new Psr7\Uri((string) $url);
            $request = new Psr7\Request('GET', $uri, ['Accept' => 'text/html']);
            /** @var Psr7\Response $guzzleResponse */
            $guzzleResponse = $this->client->send($request, $options);
        }

        $contentType = $guzzleResponse->getHeader('Content-Type');

        return [
            'url'         => (string) $url,
            'statusCode'  => $guzzleResponse->getStatusCode(),
            'contentType' => is_array($contentType) ? reset($contentType) : $contentType,
            'content'     => $guzzleResponse->getBody()->getContents(),
            'headers'     => $guzzleResponse->getHeaders(),
            'info'        => [],
        ];
    }
}
