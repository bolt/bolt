<?php

namespace Bolt\Tests\Helper;

use Bolt\Collection\Bag;
use Bolt\Controller\Backend\General;
use Bolt\Controller\Zone;
use Bolt\Helpers\RequestSanitiser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @covers \Bolt\Helpers\RequestSanitiser
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RequestSanitiserTest extends TestCase
{
    private $attributes = [
            '_controller'   => [
                General::class,
                'dashboard',
            ],
            'zone'          => 'backend',
            '_route'        => 'dashboard',
            '_route_params' => [
                'zone' => 'backend',
            ],
        ];
    private $cookies = [
            'bolt_authtoken_abc' => 'cde',
            'bolt_session_efg'   => 'hij',
        ];
    private $server = [
          'USER'                           => 'gawain',
          'HOME'                           => '/home/gawain',
          'HTTP_COOKIE'                    => 'bolt_authtoken_abc=cde; bolt_session_efg=hij',
          'HTTP_ACCEPT_LANGUAGE'           => 'en-GB,en;q=0.9',
          'HTTP_ACCEPT_ENCODING'           => 'gzip, deflate',
          'HTTP_REFERER'                   => 'http://bolt.test/bolt/login',
          'HTTP_ACCEPT'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
          'HTTP_DNT'                       => '1',
          'HTTP_USER_AGENT'                => 'Boltzilla',
          'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
          'HTTP_CACHE_CONTROL'             => 'max-age=0',
          'HTTP_CONNECTION'                => 'keep-alive',
          'HTTP_HOST'                      => 'bolt.test',
          'REDIRECT_STATUS'                => '200',
          'SERVER_NAME'                    => 'bolt.test',
          'SERVER_PORT'                    => '80',
          'SERVER_ADDR'                    => '127.0.0.1',
          'REMOTE_PORT'                    => '1024',
          'REMOTE_ADDR'                    => '127.0.0.1',
          'SERVER_SOFTWARE'                => 'nginx/2.1.2',
          'GATEWAY_INTERFACE'              => 'CGI/1.1',
          'REQUEST_SCHEME'                 => 'http',
          'SERVER_PROTOCOL'                => 'HTTP/1.1',
          'DOCUMENT_ROOT'                  => '/var/www/sites/bolt.test',
          'DOCUMENT_URI'                   => '/index.php',
          'REQUEST_URI'                    => '/bolt',
          'SCRIPT_NAME'                    => '/index.php',
          'CONTENT_LENGTH'                 => '',
          'CONTENT_TYPE'                   => '',
          'REQUEST_METHOD'                 => 'GET',
          'QUERY_STRING'                   => '',
          'SCRIPT_FILENAME'                => '/var/www/sites/bolt.test/index.php',
          'FCGI_ROLE'                      => 'RESPONDER',
          'PHP_SELF'                       => '/index.php',
        ];

    public function testFilterKeys()
    {
        $expected = ['attributes', 'query', 'files', 'cookies', 'headers', 'server', 'session'];
        $request = $this->getRequest();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $result = RequestSanitiser::filter($request);

        $this->assertInstanceOf(Bag::class, $result);
        $this->assertSame($expected, $result->keys()->toArray());
    }

    public function providerFilterBags()
    {
        return [
            'Attributes bag' => [
                'attributes',
                [],
                [
                    // The _controller key is a callable, so will be removed
                    'zone'          => 'backend',
                    '_route'        => 'dashboard',
                    '_route_params' => '{"zone":"backend"}',
                ],
            ],
            'Cookies bag' => [
                'cookies',
                [],
                $this->cookies,
            ],
            'Server bag' => [
                'server',
                [],
                $this->server,
            ],
            'Flat string values' => [
                'attributes',
                [
                    'task'   => 'testing',
                    'result' => 'passing',
                ],
                [
                    'zone'          => 'backend',
                    '_route'        => 'dashboard',
                    '_route_params' => '{"zone":"backend"}',
                    'task'          => 'testing',
                    'result'        => 'passing',
                ],
            ],
            'Nested associative array' => [
                'attributes',
                [
                    'params' => [
                        'id'          => 42,
                        'zone'        => Zone::BACKEND,
                        'contenttype' => 'koalas',
                    ],
                ],
                [
                    'zone'          => 'backend',
                    '_route'        => 'dashboard',
                    '_route_params' => '{"zone":"backend"}',
                    'params'        => '{"id":42,"zone":"backend","contenttype":"koalas"}',
                ],
            ],
            'Nested associative containing indexed array' => [
                'attributes',
                [
                    'koalas' => [
                        [
                            'id'          => 24,
                            'zone'        => Zone::FRONTEND,
                            'contenttype' => 'dropbears',
                        ],

                        [
                            'id'          => 42,
                            'zone'        => Zone::BACKEND,
                            'contenttype' => 'koalas',
                        ],
                    ],
                ],
                [
                    'zone'          => 'backend',
                    '_route'        => 'dashboard',
                    '_route_params' => '{"zone":"backend"}',
                    'koalas'        => '[{"id":24,"zone":"frontend","contenttype":"dropbears"},{"id":42,"zone":"backend","contenttype":"koalas"}]',
                ],
            ],
            'Nested indexed array' => [
                'attributes',
                [
                    'beetles' => ['john', 'paul', 'ringo', 'george'],
                    'stones'  => ['mick', 'keith', 'ronnie', 'brian'],
                ],
                [
                    'zone'          => 'backend',
                    '_route'        => 'dashboard',
                    '_route_params' => '{"zone":"backend"}',
                    'beetles'       => '["john","paul","ringo","george"]',
                    'stones'        => '["mick","keith","ronnie","brian"]',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerFilterBags
     */
    public function testFilterBags($bagName, $values, array $expected)
    {
        $request = $this->getRequest();
        foreach (array_keys($values) as $key) {
            $request->attributes->set($key, $values[$key]);
        }

        $result = RequestSanitiser::filter($request);

        $this->assertInstanceOf(Bag::class, $result);
        $this->assertSame($expected, $result->get($bagName)->toArray());
    }

    public function providerFilterSession()
    {
        return [
            'Nested associative array' => [
                [
                    'zone'   => 'backend',
                    '_route' => 'dashboard',
                    'params' => [
                        'id'          => 42,
                        'zone'        => Zone::BACKEND,
                        'contenttype' => 'koalas',
                    ],
                ],
                '{"zone":"backend","_route":"dashboard","params":{"id":42,"zone":"backend","contenttype":"koalas"}}',
            ],
            'Nested indexed array' => [
                [
                    'zone'   => 'backend',
                    '_route' => 'dashboard',
                    'params' => [42, Zone::BACKEND, 'koalas'],
                ],
                '{"zone":"backend","_route":"dashboard","params":[42,"backend","koalas"]}',
            ],
        ];
    }

    /**
     * @dataProvider providerFilterSession
     */
    public function testFilterSession($values, $expected)
    {
        $request = $this->getRequest();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->set('herinneren', $values);

        $result = RequestSanitiser::filter($request);

        $this->assertInstanceOf(Bag::class, $result);
        $this->assertSame($expected, $result->get('session')->get('herinneren'));
    }

    /**
     * @param array                $query
     * @param array                $request
     * @param array                $attributes
     * @param array                $cookies
     * @param array                $files
     * @param array                $server
     * @param null|resource|string $content
     *
     * @return Request
     */
    private function getRequest(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        return new Request(
            $query,
            $request,
            $attributes + $this->attributes,
            $cookies + $this->cookies,
            $files,
            $server + $this->server,
            $content
        );
    }
}
