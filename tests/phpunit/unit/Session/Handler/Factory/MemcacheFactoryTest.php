<?php

namespace Bolt\Tests\Session\Handler\Factory;

use Bolt\Session\Handler\Factory\MemcacheFactory;
use Bolt\Session\OptionsBag;
use Bolt\Tests\Session\Handler\Factory\Mock\MockMemcache;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @requires extension memcache
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MemcacheFactoryTest extends TestCase
{
    public function createProvider()
    {
        return [
            'empty' => [
                [
                ],
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => 11211,
                    ],
                ],
            ],

            'connection string - only host' => [
                [
                    'connection' => '10.0.0.1',
                ],
                [
                    [
                        'host' => '10.0.0.1',
                    ],
                ],
            ],

            'connection string - host and port' => [
                [
                    'connection' => '10.0.0.1:11212',
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 11212,
                    ],
                ],
            ],

            'connection string with scheme' => [
                [
                    'connection' => 'tcp://10.0.0.1:11212',
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 11212,
                    ],
                ],
            ],

            'connection array' => [
                [
                    'connection' => [
                        'host'           => '10.0.0.1',
                        'port'           => 11212,
                        'persistent'     => true,
                        'timeout'        => 5,
                        'retry_interval' => 5,
                        'weight'         => 3,
                    ],
                ],
                [
                    [
                        'host'           => '10.0.0.1',
                        'port'           => 11212,
                        'persistent'     => true,
                        'timeout'        => 5,
                        'retry_interval' => 5,
                        'weight'         => 3,
                    ],
                ],
            ],

            'connection unix socket string' => [
                [
                    'connection' => '/path/to/memcache.sock',
                ],
                [
                    [
                        'host' => 'unix:///path/to/memcache.sock',
                        'port' => 0,
                    ],
                ],
            ],

            'connection unix socket array' => [
                [
                    'connection' => [
                        'path' => '/path/to/memcache.sock',
                    ],
                ],
                [
                    [
                        'host' => 'unix:///path/to/memcache.sock',
                        'port' => 0,
                    ],
                ],
            ],

            // deprecated
            'connection at root' => [
                [
                    'host'       => '10.0.0.1',
                    'port'       => 11212,
                    'timeout'    => 34,
                    'persistent' => true,
                ],
                [
                    [
                        'host'       => '10.0.0.1',
                        'port'       => 11212,
                        'timeout'    => 34.0,
                        'persistent' => true,
                    ],
                ],
            ],

            'connections strings' => [
                [
                    'connections' => [
                        '10.0.0.1:11212',
                        '10.0.0.2:11212',
                        '10.0.0.3',
                    ],
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 11212,
                    ],
                    [
                        'host' => '10.0.0.2',
                        'port' => 11212,
                    ],
                    [
                        'host' => '10.0.0.3',
                    ],
                ],
            ],

            'connections arrays' => [
                [
                    'connections' => [
                        [
                            'host'           => '10.0.0.1',
                            'port'           => 11212,
                            'persistent'     => true,
                            'timeout'        => 5,
                            'retry_interval' => 5,
                            'weight'         => 3,
                        ],
                        [
                            'host'           => '10.0.0.2',
                            'port'           => 11213,
                            'persistent'     => true,
                            'timeout'        => 6,
                            'retry_interval' => 7,
                            'weight'         => 3,
                        ],
                    ],
                ],
                [
                    [
                        'host'           => '10.0.0.1',
                        'port'           => 11212,
                        'persistent'     => true,
                        'timeout'        => 5,
                        'retry_interval' => 5,
                        'weight'         => 3,
                    ],
                    [
                        'host'           => '10.0.0.2',
                        'port'           => 11213,
                        'persistent'     => true,
                        'timeout'        => 6,
                        'retry_interval' => 7,
                        'weight'         => 3,
                    ],
                ],
            ],

            'save path single host' => [
                [
                    'save_path' => 'host1:11212?weight=2&timeout=2&prefix=foo&persistent=1',
                ],
                [
                    [
                        'host'       => 'host1',
                        'port'       => 11212,
                        'persistent' => true,
                        'timeout'    => 2,
                        'weight'     => 2,
                    ],
                ],
            ],

            'save path single host with scheme' => [
                [
                    'save_path' => 'tcp://host1:11212?weight=2&timeout=2&prefix=foo&persistent=1',
                ],
                [
                    [
                        'host'       => 'host1',
                        'port'       => 11212,
                        'persistent' => true,
                        'timeout'    => 2,
                        'weight'     => 2,
                    ],
                ],
            ],

            'save path multiple hosts' => [
                [
                    'save_path' => 'tcp://host1:11212?weight=2&timeout=2&persistent=1, tcp://host2:11211?weight=2&timeout=2.5, host3:11211, tcp://host4',
                ],
                [
                    [
                        'host'       => 'host1',
                        'port'       => 11212,
                        'persistent' => true,
                        'timeout'    => 2,
                        'weight'     => 2,
                    ],
                    [
                        'host'    => 'host2',
                        'timeout' => 2,
                        'weight'  => 2,
                    ],
                    [
                        'host' => 'host3',
                    ],
                    [
                        'host' => 'host4',
                    ],
                ],
            ],

            'save path unix path scheme and slashes' => [
                [
                    'save_path' => 'unix:///var/run/memcache.sock?persistent=1&weight=2',
                ],
                [
                    [
                        'host'       => 'unix:///var/run/memcache.sock',
                        'port'       => 0,
                        'persistent' => true,
                        'weight'     => 2,
                    ],
                ],
            ],
            'save path unix path no scheme' => [
                [
                    'save_path' => '/var/run/memcache.sock',
                ],
                [
                    [
                        'host' => 'unix:///var/run/memcache.sock',
                        'port' => 0,
                    ],
                ],
            ],
            'save path unix path scheme' => [
                [
                    'save_path' => 'unix:/var/run/memcache.sock',
                ],
                [
                    [
                        'host' => 'unix:///var/run/memcache.sock',
                        'port' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createProvider
     *
     * @param array $sessionOptions
     * @param array $expectedConnections
     */
    public function testCreate($sessionOptions, $expectedConnections)
    {
        $sessionOptions = new OptionsBag($sessionOptions);

        $factory = new MemcacheFactory(MockMemcache::class);

        /** @var MockMemcache $memcache */
        $memcache = $factory->create($sessionOptions);

        $this->assertInstanceOf(MockMemcache::class, $memcache);

        $this->assertServers($expectedConnections, $memcache->getServers());
    }

    /**
     * @param array        $expected
     * @param OptionsBag[] $actual
     */
    private function assertServers($expected, $actual)
    {
        $expectedServers = [];

        foreach ($expected as $item) {
            $expectedServers[] = $item + [
                'host'           => '127.0.0.1',
                'port'           => 11211,
                'weight'         => 1,
                'persistent'     => false,
                'timeout'        => 1,
                'retry_interval' => 0,
            ];
        }

        $this->assertEquals($expectedServers, $actual);
    }
}
