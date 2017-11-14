<?php

namespace Bolt\Embed;

use Embed\Http\DispatcherInterface;
use Embed\Http\ImageResponse;
use Embed\Http\Response;
use Embed\Http\Url;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;

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
    private $client;
    /** @var \Embed\Http\AbstractResponse[] */
    private $responses = [];
    /** @var HandlerStack */
    private $handlerStack;

    /**
     * Constructor.
     *
     * @param Client       $client
     * @param HandlerStack $handlerStack
     */
    public function __construct(Client $client, HandlerStack $handlerStack)
    {
        $this->client = $client;
        $this->handlerStack = $handlerStack;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function dispatchImages(array $urls)
    {
        $responses = [];
        $promises = [];
        $mimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/x-icon',
        ];

        /** @var Url $url */
        foreach ($urls as $key => $url) {
            if ($url->getScheme() === 'data') {
                $response = ImageResponse::createFromBase64($url);
                if ($response) {
                    $responses[$key] = $response;
                }

                continue;
            }
            $callable = function (ResponseInterface $response) use ($url) {
                return $response->withHeader('X-Embed-Request-Uri', $url);
            };
            $this->handlerStack->push(Middleware::mapResponse($callable), 'embed-request');
            $promises[$key] = $this->client->getAsync((string) $url);
            $this->handlerStack->remove('embed-request');
        }

        $results = Promise\settle($promises)->wait();
        foreach ($results as $result) {
            if (!isset($result['value'])) {
                continue;
            }

            /** @var Psr7\Response $response */
            $response = $result['value'];
            $body = (string) $response->getBody();
            $baseUrl = (array) $response->getHeader('X-Embed-Request-Uri');
            $redirectUriHistory = $response->getHeader('X-Guzzle-Redirect-History');
            $redirectUri = $redirectUriHistory ? array_pop($redirectUriHistory) : reset($baseUrl);

            $mimeType = $this->getMimeType($response);
            if (!in_array($mimeType, $mimeTypes)) {
                continue;
            }

            $imageInfo = getimagesizefromstring($body);
            if ($imageInfo === false) {
                continue;
            }

            $responses[] = $this->responses[] = new ImageResponse(
                Url::create(reset($baseUrl)),
                Url::create($redirectUri),
                $response->getStatusCode(),
                $response->getHeader('Content-Type'),
                $imageInfo,
                $response->getHeaders(),
                []
            );
        }

        return array_values($responses);
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
            $response = $this->client->get((string) $url, $options);
            $redirectUri = $response->getEffectiveUrl() ?: (string) $url;
        } else {
            $allowRedirects = $this->client->getConfig('allow_redirects');
            $allowRedirects['max'] = 10;
            $allowRedirects['track_redirects'] = true;
            $options['allow_redirects'] = $allowRedirects;

            $uri = new Psr7\Uri((string) $url);
            $request = new Psr7\Request('GET', $uri, ['Accept' => 'text/html']);
            /** @var Psr7\Response $response */
            $response = $this->client->send($request, $options);
            $redirectUriHistory = $response->getHeader('X-Guzzle-Redirect-History');
            $redirectUri = $redirectUriHistory ? array_pop($redirectUriHistory) : (string) $url;
        }

        return [
            'url'         => $redirectUri,
            'statusCode'  => $response->getStatusCode(),
            'contentType' => $this->getMimeType($response),
            'content'     => $response->getBody()->getContents(),
            'headers'     => $response->getHeaders(),
            'info'        => [],
        ];
    }

    /**
     * @param Psr7\Response $response
     *
     * @return string
     */
    private function getMimeType(Psr7\Response $response)
    {
        $header = $response->getHeader('Content-Type');
        if ($header !== []) {
            return reset($header);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $response->getBody()->getContents());
        finfo_close($finfo);

        return $mime;
    }
}
