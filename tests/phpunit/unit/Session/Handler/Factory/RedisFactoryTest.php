<?php

namespace Bolt\Tests\Session\Handler\Factory;

use Bolt\Session\Handler\Factory\RedisFactory;
use Bolt\Session\OptionsBag;
use Bolt\Tests\Session\Handler\Factory\Mock\MockRedis;
use PHPUnit_Framework_TestCase as TestCase;
use Redis;

/**
 * @requires extension redis
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class RedisFactoryTest extends TestCase
{
    public function parseProvider()
    {
        return [
            'empty' => [
                [
                ],
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => 6379,
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
                    'connection' => '10.0.0.1:6380',
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 6380,
                    ],
                ],
            ],

            'connection string with scheme' => [
                [
                    'connection' => 'tcp://10.0.0.1:6380',
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 6380,
                    ],
                ],
            ],

            'connection array' => [
                [
                    'connection' => [
                        'host'           => '10.0.0.1',
                        'port'           => 6380,
                        'persistent'     => true,
                        'timeout'        => 5.0,
                        'retry_interval' => 500,
                        'weight'         => 3,
                        'database'       => 4,
                        'auth'           => 'secret', // deprecated
                    ],
                    'options' => [
                        'prefix' => 'foo',
                    ],
                ],
                [
                    [
                        'host'           => '10.0.0.1',
                        'port'           => 6380,
                        'persistent'     => true,
                        'timeout'        => 5.0,
                        'retry_interval' => 500,
                        'weight'         => 3,
                        'database'       => 4,
                        'prefix'         => 'foo',
                        'password'       => 'secret',
                    ],
                ],
            ],

            'connection unix socket string' => [
                [
                    'connection' => '/path/to/redis.sock',
                ],
                [
                    [
                        'host' => '/path/to/redis.sock',
                        'port' => 0,
                    ],
                ],
            ],

            'connection unix socket array' => [
                [
                    'connection' => [
                        'path' => '/path/to/redis.sock',
                    ],
                ],
                [
                    [
                        'host' => '/path/to/redis.sock',
                        'port' => 0,
                    ],
                ],
            ],

            // deprecated
            'connection at root' => [
                [
                    'host'       => 'redis.test',
                    'port'       => 6380,
                    'timeout'    => 34.0,
                    'persistent' => true,
                    'password'   => 'secret',
                    'database'   => 4,
                    'prefix'     => 'foo',
                ],
                [
                    [
                        'host'       => 'redis.test',
                        'port'       => 6380,
                        'timeout'    => 34.0,
                        'persistent' => true,
                        'password'   => 'secret',
                        'database'   => 4,
                        'prefix'     => 'foo',
                    ],
                ],
            ],

            'connections strings' => [
                [
                    'connections' => [
                        '10.0.0.1:6380',
                        '10.0.0.2:6380',
                        '10.0.0.3',
                    ],
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 6380,
                    ],
                    [
                        'host' => '10.0.0.2',
                        'port' => 6380,
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
                            'port'           => 6380,
                            'persistent'     => true,
                            'timeout'        => 5.0,
                            'retry_interval' => 500,
                            'weight'         => 3,
                            'database'       => 4,
                            'auth'           => 'secret', // deprecated
                        ],
                        [
                            'host'           => '10.0.0.2',
                            'port'           => 6381,
                            'persistent'     => true,
                            'timeout'        => 6.0,
                            'retry_interval' => 501,
                            'weight'         => 3,
                            'database'       => 3,
                            'password'       => 'secret2',
                        ],
                    ],
                    'options' => [
                        'prefix' => 'foo',
                    ],
                ],
                [
                    [
                        'host'           => '10.0.0.1',
                        'port'           => 6380,
                        'persistent'     => true,
                        'timeout'        => 5.0,
                        'retry_interval' => 500,
                        'weight'         => 3,
                        'database'       => 4,
                        'prefix'         => 'foo',
                        'password'       => 'secret',
                    ],
                    [
                        'host'           => '10.0.0.2',
                        'port'           => 6381,
                        'persistent'     => true,
                        'timeout'        => 6.0,
                        'retry_interval' => 501,
                        'weight'         => 3,
                        'database'       => 3,
                        'prefix'         => 'foo',
                        'password'       => 'secret2',
                    ],
                ],
            ],

            'save path single host' => [
                [
                    'save_path' => 'tcp://host1:6381?weight=2&timeout=2.5&database=3&prefix=foo&auth=secret&persistent=1',
                ],
                [
                    [
                        'host'       => 'host1',
                        'port'       => 6381,
                        'persistent' => true,
                        'timeout'    => 2.5,
                        'weight'     => 2,
                        'database'   => 3,
                        'prefix'     => 'foo',
                        'password'   => 'secret',
                    ],
                ],
            ],

            'save path single host without scheme' => [
                [
                    'save_path' => 'host1:6381',
                ],
                [
                    [
                        'host' => 'host1',
                        'port' => 6381,
                    ],
                ],
            ],

            'save path multiple hosts' => [
                [
                    'save_path' => 'tcp://host1:6381?weight=2&timeout=2.5&database=3&prefix=foo&auth=secret&persistent=1, tcp://host2:6379?weight=2&timeout=2.5, host3:6379, tcp://host4',
                ],
                [
                    [
                        'host'       => 'host1',
                        'port'       => 6381,
                        'persistent' => true,
                        'timeout'    => 2.5,
                        'weight'     => 2,
                        'database'   => 3,
                        'prefix'     => 'foo',
                        'password'   => 'secret',
                    ],
                    [
                        'host'    => 'host2',
                        'port'    => 6379,
                        'timeout' => 2.5,
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
                    'save_path' => 'unix:///var/run/redis/redis.sock?persistent=1&weight=2&database=1',
                ],
                [
                    [
                        'host'       => '/var/run/redis/redis.sock',
                        'port'       => 0,
                        'persistent' => true,
                        'weight'     => 2,
                        'database'   => 1,
                    ],
                ],
            ],
            'save path unix path no scheme' => [
                [
                    'save_path' => '/var/run/redis/redis.sock',
                ],
                [
                    [
                        'host' => '/var/run/redis/redis.sock',
                        'port' => 0,
                    ],
                ],
            ],
            'save path unix path scheme' => [
                [
                    'save_path' => 'unix:/var/run/redis/redis.sock',
                ],
                [
                    [
                        'host' => '/var/run/redis/redis.sock',
                        'port' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider parseProvider
     *
     * @param array $sessionOptions
     * @param array $expectedConnections
     */
    public function testParse($sessionOptions, $expectedConnections)
    {
        $sessionOptions = new OptionsBag($sessionOptions);

        $factory = new RedisFactory();

        $connections = $factory->parse($sessionOptions);

        $this->assertConnections($expectedConnections, $connections);
    }

    /**
     * @param array        $expected
     * @param OptionsBag[] $actual
     */
    private function assertConnections($expected, $actual)
    {
        $expectedConnections = [];

        foreach ($expected as $item) {
            $expectedConnections[] = $item + [
                'host'           => '127.0.0.1',
                'port'           => 6379,
                'persistent'     => false,
                'timeout'        => 86400.0,
                'retry_interval' => 0,
                'weight'         => 1,
                'database'       => 0,
                'prefix'         => 'PHPREDIS_SESSION:',
                'password'       => null,
            ];
        }

        $actualConnections = [];

        foreach ($actual as $item) {
            $actualConnections[] = $item->all();
        }

        $this->assertEquals($expectedConnections, $actualConnections);
    }

    public function testCreate()
    {
        $sessionOptions = new OptionsBag([
            'connection' => [
                'host'       => '10.0.0.1',
                'port'       => 6380,
                'persistent' => true,
                'timeout'    => 5.0,
                'weight'     => 3,
                'database'   => 4,
                'prefix'     => 'foo',
                'password'   => 'secret',
            ],
        ]);

        $factory = new RedisFactory(MockRedis::class);

        /** @var MockRedis $redis */
        $redis = $factory->create($sessionOptions);

        $this->assertInstanceOf(MockRedis::class, $redis);

        $this->assertEquals('10.0.0.1', $redis->host);
        $this->assertEquals(6380, $redis->port);
        $this->assertTrue($redis->persistent);
        $this->assertEquals(5.0, $redis->timeout);
        $this->assertEquals(4, $redis->database);
        $this->assertEquals('foo', $redis->getOption(Redis::OPT_PREFIX));
        $this->assertEquals('secret', $redis->password);
    }
}
