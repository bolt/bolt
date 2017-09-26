<?php

namespace Bolt\Tests\Embed;

use Bolt\Common\Json;
use Bolt\Embed;
use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Embed\Http\ImageResponse;
use Embed\Http\Response;
use Embed\Http\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Embed\GuzzleDispatcher
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GuzzleDispatcherTest extends TestCase
{
    public function testDispatch()
    {
        $requestUrl = 'https://www.youtube.com/watch?v=x4IDM3ltTYo';
        $content = [
            'thumbnail_height' => 360,
            'thumbnail_url'    => 'https://i.ytimg.com/vi/x4IDM3ltTYo/hqdefault.jpg',
            'provider_url'     => 'https://www.youtube.com/',
            'type'             => 'video',
            'author_name'      => 'Silversun Pickups',
            'height'           => 270,
            'version'          => '1.0',
            'thumbnail_width'  => 480,
            'title'            => 'Silversun Pickups - Nightlight (Official Video)',
            'author_url'       => 'https://www.youtube.com/user/Silversunpickups',
            'provider_name'    => 'YouTube',
            'width'            => 480,
            'html'             => '<iframe width="480" height="270" src="https://www.youtube.com/embed/x4IDM3ltTYo?feature=oembed" frameborder="0" allowfullscreen></iframe>',
        ];
        $mock = new MockHandler([
            new Psr7\Response(200, ['Content-Type' => 'application/json'], Json::dump($content)),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $dispatcher = new Embed\GuzzleDispatcher($client, $handler);
        $url = Url::create($requestUrl);

        $response = $dispatcher->dispatch($url);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getContentType());
        $this->assertSame($content, Json::parse($response->getContent()));
    }

    public function testDispatchImages()
    {
        $imageUrl = 'https://www.youtube.com/yts/img/image.png';
        $thumbnailUrl = 'https://www.youtube.com/yts/img/thumbnail.png';

        $filesystem = new Filesystem(new Local(__DIR__ . '/../resources'));
        /** @var \Bolt\Filesystem\Handler\Image $mockFile */
        $mockFile = $filesystem->getFile('generic-logo.png');
        $fileData = $mockFile->readStream();

        $mockHandler = new MockHandler([
            new Psr7\Response(200, ['Content-Type' => 'image/png'], $fileData),
            new Psr7\Response(200, ['Content-Type' => 'image/png'], $fileData),
        ]);

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $dispatcher = new Embed\GuzzleDispatcher($client, $handler);
        $urls = [
            Url::create($imageUrl),
            Url::create($thumbnailUrl),
        ];

        $responses = $dispatcher->dispatchImages($urls);
        foreach ($responses as $response) {
            $this->assertInstanceOf(ImageResponse::class, $response);
        }

        /** @var ImageResponse $response */
        $response = $responses[0];
        $this->assertSame($imageUrl, (string) $response->getUrl());
        $this->assertSame($imageUrl, (string) $response->getStartingUrl());

        $response = $responses[1];
        $this->assertSame($thumbnailUrl, (string) $response->getUrl());
        $this->assertSame($thumbnailUrl, (string) $response->getStartingUrl());
    }
}
