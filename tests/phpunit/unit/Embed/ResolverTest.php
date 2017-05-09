<?php

namespace Bolt\Tests\Embed;

use Bolt\Embed;
use Bolt\Tests\BoltUnitTest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;

/**
 * Class to test src/Embed/Resolver.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ResolverTest extends BoltUnitTest
{
    public function testOembed()
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
            new Psr7\Response(200),
            new Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode($content)),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $factory = function ($url, $options = []) use ($requestUrl, $client) {
            $dispatcher = new Embed\GuzzleDispatcher($client);
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
    }
}
