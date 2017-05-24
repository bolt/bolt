<?php

namespace Bolt\Tests\Session\Handler\Factory;

use Bolt\Session\Handler\Factory\MemcachedFactory;
use Bolt\Session\OptionsBag;
use Bolt\Tests\Session\Handler\Factory\Mock\MockMemcached;
use Memcached;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @requires extension memcached
 * @requires extension memcached 2.0.0
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MemcachedFactoryTest extends TestCase
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
                        'host'   => '10.0.0.1',
                        'port'   => 11212,
                        'weight' => 3,
                    ],
                ],
                [
                    [
                        'host'   => '10.0.0.1',
                        'port'   => 11212,
                        'weight' => 3,
                    ],
                ],
            ],

            'connection unix socket string' => [
                [
                    'connection' => '/path/to/memcache.sock',
                ],
                [
                    [
                        'host' => '/path/to/memcache.sock',
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
                        'host' => '/path/to/memcache.sock',
                        'port' => 0,
                    ],
                ],
            ],

            // deprecated
            'connection at root' => [
                [
                    'host' => '10.0.0.1',
                    'port' => 11212,
                ],
                [
                    [
                        'host' => '10.0.0.1',
                        'port' => 11212,
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
                            'host'   => '10.0.0.1',
                            'port'   => 11212,
                            'weight' => 3,
                        ],
                        [
                            'host'   => '10.0.0.2',
                            'port'   => 11213,
                            'weight' => 3,
                        ],
                    ],
                ],
                [
                    [
                        'host'   => '10.0.0.1',
                        'port'   => 11212,
                        'weight' => 3,
                    ],
                    [
                        'host'   => '10.0.0.2',
                        'port'   => 11213,
                        'weight' => 3,
                    ],
                ],
            ],

            'save path host' => [
                [
                    'save_path' => 'host1:11212:2',
                ],
                [
                    [
                        'host'   => 'host1',
                        'port'   => 11212,
                        'weight' => 2,
                    ],
                ],
            ],

            // for v2 of extension. support will be removed once PHP 5 support is dropped.
            'save path with persistent id' => [
                [
                    'save_path' => 'PERSISTENT=foo host1:11212:2',
                ],
                [
                    [
                        'host'   => 'host1',
                        'port'   => 11212,
                        'weight' => 2,
                    ],
                ],
            ],

            'save path unix path' => [
                [
                    'save_path' => '/var/run/memcache.sock',
                ],
                [
                    [
                        'host' => '/var/run/memcache.sock',
                        'port' => 0,
                    ],
                ],
            ],

            'save path unix path with weight' => [
                [
                    'save_path' => '/var/run/memcache.sock:0:2',
                ],
                [
                    [
                        'host'   => '/var/run/memcache.sock',
                        'port'   => 0,
                        'weight' => 2,
                    ],
                ],
            ],

            'save path multiple' => [
                [
                    'save_path' => 'host1:11212:2, /var/run/memcache.sock:0:4, host3:11211, host4',
                ],
                [
                    [
                        'host'   => 'host1',
                        'port'   => 11212,
                        'weight' => 2,
                    ],
                    [
                        'host'   => '/var/run/memcache.sock',
                        'port'   => 0,
                        'weight' => 4,
                    ],
                    [
                        'host' => 'host3',
                    ],
                    [
                        'host' => 'host4',
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

        $factory = new MemcachedFactory();

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
                'host'   => '127.0.0.1',
                'port'   => 11211,
                'weight' => 1,
            ];
        }

        $actualConnections = [];

        foreach ($actual as $item) {
            $actualConnections[] = $item->all();
        }

        $this->assertEquals($expectedConnections, $actualConnections);
    }

    public function parseOptionsProvider()
    {
        return [
            'all options from user' => [
                [
                    'options' => [
                        'persistent'             => true,
                        'binary_protocol'        => true,
                        'consistent_hash'        => true,
                        'server_failure_limit'   => 5,
                        'remove_failed_servers'  => true,
                        'randomize_replica_read' => true,
                        'number_of_replicas'     => 5,
                        'connect_timeout'        => 50,
                        'username'               => 'admin',
                        'password'               => 'password',
                        'prefix'                 => 'sessions:',
                    ],
                ],
                [
                    'persistent'             => false,
                    'binary_protocol'        => false,
                    'consistent_hash'        => false,
                    'server_failure_limit'   => 0,
                    'remove_failed_servers'  => false,
                    'randomize_replica_read' => false,
                    'number_of_replicas'     => 0,
                    'connect_timeout'        => 0,
                    'sasl_username'          => 'herp',
                    'sasl_password'          => 'derp',
                ],
                [
                    'persistent'             => true,
                    'binary_protocol'        => true,
                    'consistent_hash'        => true,
                    'server_failure_limit'   => true,
                    'remove_failed_servers'  => true,
                    'randomize_replica_read' => true,
                    'number_of_replicas'     => 5,
                    'connect_timeout'        => 50,
                    'username'               => 'admin',
                    'password'               => 'password',
                    'prefix'                 => 'sessions:',
                ],
            ],

            'no options from user' => [
                [],
                [
                    'persistent'             => true,
                    'binary_protocol'        => true,
                    'consistent_hash'        => true,
                    'server_failure_limit'   => 5,
                    'remove_failed_servers'  => true,
                    'randomize_replica_read' => true,
                    'number_of_replicas'     => 5,
                    'connect_timeout'        => 50,
                    'sasl_username'          => 'admin',
                    'sasl_password'          => 'password',
                    'prefix'                 => 'sessions:',
                ],
                [
                    'persistent'             => true,
                    'binary_protocol'        => true,
                    'consistent_hash'        => true,
                    'server_failure_limit'   => 5,
                    'remove_failed_servers'  => true,
                    'randomize_replica_read' => true,
                    'number_of_replicas'     => 5,
                    'connect_timeout'        => 50,
                    'username'               => 'admin',
                    'password'               => 'password',
                    'prefix'                 => 'sessions:',
                ],
            ],

            'v2 options from user are ignored if v3 are specified as well' => [
                [
                    'options' => [
                        'remove_failed'         => false,
                        'remove_failed_servers' => true,

                        'persistent'             => true,
                        'binary_protocol'        => true,
                        'consistent_hash'        => true,
                        'server_failure_limit'   => 5,
                        'randomize_replica_read' => true,
                        'number_of_replicas'     => 5,
                        'connect_timeout'        => 50,
                        'sasl_username'          => 'admin',
                        'sasl_password'          => 'password',
                    ],
                ],
                [
                ],
                [
                    'remove_failed_servers' => true,

                    'persistent'             => true,
                    'binary_protocol'        => true,
                    'consistent_hash'        => true,
                    'server_failure_limit'   => 5,
                    'randomize_replica_read' => true,
                    'number_of_replicas'     => 5,
                    'connect_timeout'        => 50,
                    'username'               => 'admin',
                    'password'               => 'password',
                ],
            ],

            'v2 options from user are replaced with v3 options' => [
                [
                    'options' => [
                        'remove_failed' => true,

                        'persistent'             => true,
                        'binary_protocol'        => true,
                        'consistent_hash'        => true,
                        'server_failure_limit'   => 5,
                        'randomize_replica_read' => true,
                        'number_of_replicas'     => 5,
                        'connect_timeout'        => 50,
                        'username'               => 'admin',
                        'password'               => 'password',
                    ],
                ],
                [
                ],
                [
                    'remove_failed_servers' => true,

                    'persistent'             => true,
                    'binary_protocol'        => true,
                    'consistent_hash'        => true,
                    'server_failure_limit'   => 5,
                    'randomize_replica_read' => true,
                    'number_of_replicas'     => 5,
                    'connect_timeout'        => 50,
                    'username'               => 'admin',
                    'password'               => 'password',
                ],
            ],

            'v2 options are defaulted from ini' => [
                [
                    'options' => [
                        'persistent'             => true,
                        'consistent_hash'        => true,
                        'server_failure_limit'   => 5,
                        'randomize_replica_read' => true,
                        'number_of_replicas'     => 5,
                        'connect_timeout'        => 50,
                        'sasl_username'          => 'admin',
                        'sasl_password'          => 'password',
                    ],
                ],
                [
                    'remove_failed' => true,
                    'binary'        => true,
                ],
                [
                    'remove_failed_servers' => true,
                    'binary_protocol'       => true,

                    'persistent'             => true,
                    'consistent_hash'        => true,
                    'server_failure_limit'   => 5,
                    'randomize_replica_read' => true,
                    'number_of_replicas'     => 5,
                    'connect_timeout'        => 50,
                    'username'               => 'admin',
                    'password'               => 'password',
                ],
            ],
        ];
    }

    /**
     * @dataProvider parseOptionsProvider
     *
     * @param $sessionOptions
     * @param $iniValues
     * @param $expected
     */
    public function testParseOptions($sessionOptions, $iniValues, $expected)
    {
        $sessionOptions = new OptionsBag($sessionOptions);
        $ini = new OptionsBag($iniValues);

        $factory = new MemcachedFactory($ini);

        $parseOptions = new \ReflectionMethod($factory, 'parseOptions');
        $parseOptions->setAccessible(true);

        /** @var OptionsBag $actual */
        $actual = $parseOptions->invoke($factory, $sessionOptions);

        $this->assertInstanceOf(OptionsBag::class, $actual);

        $this->assertEquals($expected, $actual->all());
    }

    public function parsePersistentIdProvider()
    {
        return [
            'save path' => [
                [
                    'save_path' => 'PERSISTENT=foo host1:11212:2',
                ],
                'foo',
            ],

            'persistent_id option' => [
                [
                    'options' => [
                        'persistent_id' => 'foo',
                    ],
                ],
                'foo',
            ],

            'automatically - options #1' => [
                [
                    'connection' => '10.0.0.1',
                    'options'    => [
                        'username' => 'admin',
                        'password' => 'secret',
                    ],
                ],
                '46c7e1ddb20f90417c9afece27e3425de24925d3ef83948a71eee5d0ba1875ff',
            ],

            'automatically - options #2' => [
                [
                    'connection' => '10.0.0.1',
                    'options'    => [
                        'username' => 'admin2',
                        'password' => 'secret2',
                    ],
                ],
                'e9d327285e9884438896bbcce238504ac1d0116385ca0d5b7ce3d6f55b59fd35',
            ],

            'automatically - connection #1' => [
                [
                    'connection' => '10.0.0.1',
                ],
                '7008acbaf8ca22813791d882cfcc3bef3c4ddd98c211feea1ac6c16747f68061',
            ],

            'automatically - connection #2' => [
                [
                    'connection' => '10.0.0.2',
                ],
                'e387cfa01af95592ca93630442178c027a7b7d879cb1213316b3f801cf9b5d6c',
            ],
        ];
    }

    /**
     * @dataProvider parsePersistentIdProvider
     *
     * @param $sessionOptions
     * @param $expected
     */
    public function testPersistentIdFromSavePath($sessionOptions, $expected)
    {
        $sessionOptions = new OptionsBag($sessionOptions);

        $factory = new MemcachedFactory(new ParameterBag());

        $connections = $factory->parse($sessionOptions);

        $parseOptions = new \ReflectionMethod($factory, 'parseOptions');
        $parseOptions->setAccessible(true);

        /** @var OptionsBag $actual */
        $options = $parseOptions->invoke($factory, $sessionOptions);

        $parsePersistentId = new \ReflectionMethod($factory, 'parsePersistentId');
        $parsePersistentId->setAccessible(true);

        $id = $parsePersistentId->invoke($factory, $connections, $options, $sessionOptions);

        $this->assertEquals($expected, $id);
    }

    public function testCreate()
    {
        $sessionOptions = new OptionsBag([
            'connections' => [
                [
                    'host'   => '10.0.0.1',
                    'port'   => 11212,
                    'weight' => 4,
                ],
                '10.0.0.2',
                '/var/run/memcache.sock',
            ],
            'options' => [
                'persistent'             => true,
                'persistent_id'          => 'foo',
                'binary_protocol'        => false,
                'consistent_hash'        => true,
                'number_of_replicas'     => 5,
                'randomize_replica_read' => true,
                'server_failure_limit'   => 2,
                'remove_failed_servers'  => true,
                'connect_timeout'        => 10,
                'username'               => 'admin',
                'password'               => 'secret',
            ],
        ]);

        $factory = new MemcachedFactory(new ParameterBag(), MockMemcached::class);

        /** @var MockMemcached $memcached */
        $memcached = $factory->create($sessionOptions);

        $this->assertInstanceOf(MockMemcached::class, $memcached);

        $this->assertEquals('foo', $memcached->getPersistentId());

        $expectedServers = [
            ['10.0.0.1', 11212, 4],
            ['10.0.0.2', 11211, 1],
            ['/var/run/memcache.sock', 0, 1],
        ];
        $this->assertEquals($expectedServers, $memcached->getServerList());

        $expectedOptions = [
            Memcached::OPT_BINARY_PROTOCOL        => true, // enabled because username/password were given.
            Memcached::OPT_LIBKETAMA_COMPATIBLE   => true,
            Memcached::OPT_SERVER_FAILURE_LIMIT   => 2,
            Memcached::OPT_NUMBER_OF_REPLICAS     => 5,
            Memcached::OPT_RANDOMIZE_REPLICA_READ => true,
            Memcached::OPT_REMOVE_FAILED_SERVERS  => true,
            Memcached::OPT_CONNECT_TIMEOUT        => 10,
        ];
        $this->assertEquals($expectedOptions, $memcached->getOptions());

        $this->assertEquals(['admin', 'secret'], $memcached->getSaslAuthData());
    }
}
