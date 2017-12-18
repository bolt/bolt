<?php

namespace Bolt\Tests\Embed;

use Bolt\Common\Json;
use Bolt\Embed;
use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Embed\Exceptions\EmbedException;
use Embed\Exceptions\InvalidUrlException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Embed\Resolver
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ResolverTest extends TestCase
{
    public function testEmbed()
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

        $factory = function ($url, $options = []) use ($content) {
            $mock = new MockHandler([
                new Psr7\Response(200),
                new Psr7\Response(200, ['Content-Type' => 'application/json'], Json::dump($content)),
            ]);

            $handler = HandlerStack::create($mock);
            $client = new Client(['handler' => $handler]);

            $dispatcher = new Embed\GuzzleDispatcher($client, $handler);
            /** @var \Embed\Adapters\Adapter $info */
            return \Embed\Embed::create($url, $options, $dispatcher);
        };
        $url = new Psr7\Uri($requestUrl);
        $resolver = new Embed\Resolver($factory);
        $response = $resolver->embed($url, 'oembed');

        $this->assertArrayHasKey('type', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('height', $response);
        $this->assertArrayHasKey('width', $response);
        $this->assertSame($content, $response);

        $response = $resolver->embed($url, 'koala');
        $this->assertSame([], $response);
    }

    public function testImage()
    {
        $requestUrl = 'https://www.youtube.com/watch?v=x4IDM3ltTYo';
        $content = [
            'thumbnail_url' => 'https://i.ytimg.com/vi/x4IDM3ltTYo/generic-logo.png',
        ];

        $factory = function ($url, $options = []) use ($requestUrl, $content) {
            $mock = new MockHandler([
                new Psr7\Response(200),
                new Psr7\Response(200, ['Content-Type' => 'application/json'], Json::dump($content)),
                new Psr7\Response(200, ['Content-Type' => 'image/png'], file_get_contents(__DIR__ . '/../resources/generic-logo.png')),
            ]);

            $handler = HandlerStack::create($mock);
            $client = new Client(['handler' => $handler]);

            $dispatcher = new Embed\GuzzleDispatcher($client, $handler);
            /** @var \Embed\Adapters\Adapter $info */
            return \Embed\Embed::create($url, $options, $dispatcher);
        };
        $url = new Psr7\Uri($requestUrl);
        $resolver = new Embed\Resolver($factory);
        $response = $resolver->image($url);

        $this->assertSame($content['thumbnail_url'], $response);
    }

    public function testImages()
    {
        $requestUrl = 'http://www.youtube.com/watch?v=x4IDM3ltTYo';
        $content = [
            'image'         => 'https://www.youtube.com/yts/img/image.png',
            'thumbnail'     => 'https://www.youtube.com/yts/img/thumbnail.png',
        ];
        $filesystem = new Filesystem(new Local(__DIR__ . '/../resources'));
        /** @var \Bolt\Filesystem\Handler\Image $mockFile */
        $mockFile = $filesystem->getFile('generic-logo.png');
        $fileData = $mockFile->readStream();

        $factory = function ($url, $options = []) use ($requestUrl, $content, $fileData) {
            $mock = new MockHandler([
                new Psr7\Response(200),
                new Psr7\Response(200, ['Content-Type' => 'application/json'], Json::dump($content)),
                new Psr7\Response(200, ['Content-Type' => 'image/png'], $fileData),
                new Psr7\Response(200, ['Content-Type' => 'image/png'], $fileData),
            ]);

            $handler = HandlerStack::create($mock);
            $client = new Client(['handler' => $handler]);

            $dispatcher = new Embed\GuzzleDispatcher($client, $handler);
            /** @var \Embed\Adapters\Adapter $info */
            return \Embed\Embed::create($url, $options, $dispatcher);
        };
        $url = new Psr7\Uri($requestUrl);
        $resolver = new Embed\Resolver($factory);
        $response = $resolver->images($url);

        foreach (['url', 'width', 'height', 'size', 'mime'] as $key) {
            $this->assertArrayHasKey($key, $response[0]);
            $this->assertArrayHasKey($key, $response[1]);
        }

        $this->assertSame($content['image'], $response[0]['url']);
        $this->assertSame('image/png', $response[0]['mime'][0]);
        $this->assertSame($content['thumbnail'], $response[1]['url']);
        $this->assertSame('image/png', $response[1]['mime'][0]);
    }

    /**
     * @expectedException \Bolt\Exception\EmbedResolverException
     */
    public function testInvalidUrl()
    {
        $requestUrl = 'https://example.com/watch?v=nothing';
        $factory = function () {
            throw new InvalidUrlException('');
        };
        $url = new Psr7\Uri($requestUrl);
        $resolver = new Embed\Resolver($factory);
        $resolver->image($url);
    }

    /**
     * @expectedException \Bolt\Exception\EmbedResolverException
     */
    public function testEmbedException()
    {
        $requestUrl = 'https://example.com/watch?v=nothing';
        $factory = function () {
            throw new EmbedException('');
        };
        $url = new Psr7\Uri($requestUrl);
        $resolver = new Embed\Resolver($factory);
        $resolver->image($url);
    }
}
