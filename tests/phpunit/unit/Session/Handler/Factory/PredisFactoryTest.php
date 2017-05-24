<?php

namespace Bolt\Tests\Session\Handler\Factory;

use Bolt\Session\Handler\Factory\PredisFactory;
use Bolt\Session\OptionsBag;
use PHPUnit_Framework_TestCase as TestCase;
use Predis\ClientInterface;
use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Connection\Aggregate\PredisCluster;
use Predis\Connection\ParametersInterface;
use Predis\Connection\StreamConnection;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class PredisFactoryTest extends TestCase
{
    public function singleConnectionCreationProvider()
    {
        return [
            'empty' => [
                [
                ],
                [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                ],
            ],

            'connection string only host' => [
                [
                    'connection' => '10.0.0.1',
                ],
                [
                    [
                        'host' => '10.0.0.1',
                    ],
                ],
            ],

            'connection string' => [
                [
                    'connection' => 'tcp://10.0.0.1:6380?alias=master',
                ],
                [
                    [
                        'host'  => '10.0.0.1',
                        'port'  => 6380,
                        'alias' => 'master',
                    ],
                ],
            ],

            'connection array' => [
                [
                    'connection' => [
                        'host'  => '10.0.0.1',
                        'port'  => 6380,
                        'alias' => 'master',
                    ],
                ],
                [
                    [
                        'host'  => '10.0.0.1',
                        'port'  => 6380,
                        'alias' => 'master',
                    ],
                ],
            ],

            'unix socket string' => [
                [
                    'connection' => 'unix:/path/to/redis.sock?alias=master',
                ],
                [
                    [
                        'scheme' => 'unix',
                        'path'   => '/path/to/redis.sock',
                        'alias'  => 'master',
                    ],
                ],
            ],

            'unix socket array' => [
                [
                    'connection' => [
                        'scheme' => 'unix',
                        'path'   => '/path/to/redis.sock',
                    ],
                ],
                [
                    [
                        'scheme' => 'unix',
                        'path'   => '/path/to/redis.sock',
                        'alias'  => 'master',
                    ],
                ],
            ],

            'with options' => [
                [
                    'connection' => [],
                    'options'    => [
                        'prefix' => 'foo',
                    ],
                ],
                [],
                [
                    'prefix' => 'foo',
                ],
            ],

            'with prefix at root' => [
                [
                    'connection' => [],
                    'prefix'     => 'foo',
                ],
                [],
                [
                    'prefix' => 'foo',
                ],
            ],

            // deprecated
            'at root' => [
                [
                    'scheme'     => 'tcp',
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
                        'scheme'     => 'tcp',
                        'host'       => 'redis.test',
                        'port'       => 6380,
                        'timeout'    => 34.0,
                        'persistent' => true,
                        'password'   => 'secret',
                        'database'   => 4,
                    ],
                ],
                [
                    'prefix' => 'foo',
                ],
            ],
        ];
    }

    public function clusterConnectionCreationProvider()
    {
        $expectedConnections = [
            [
                'scheme' => 'tcp',
                'host'   => '10.0.0.1',
            ],
            [
                'scheme' => 'tcp',
                'host'   => '10.0.0.2',
            ],
            [
                'scheme' => 'tcp',
                'host'   => '10.0.0.3',
            ],
        ];

        return [
            'cluster strings' => [
                [
                    'connections' => [
                        'tcp://10.0.0.1',
                        'tcp://10.0.0.2',
                        'tcp://10.0.0.3',
                    ],
                ],
                $expectedConnections,
            ],

            'cluster array' => [
                [
                    'connections' => [
                        [
                            'host' => '10.0.0.1',
                        ],
                        [
                            'host' => '10.0.0.2',
                        ],
                        [
                            'host' => '10.0.0.3',
                        ],
                    ],
                ],
                $expectedConnections,
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Using "save_path" is unsupported with "predis" session handler.
     */
    public function testSavePath()
    {
        $sessionOptions = new OptionsBag([
            'save_path' => 'asdf',
        ]);

        $factory = new PredisFactory();

        $factory->create($sessionOptions);
    }

    /**
     * @dataProvider singleConnectionCreationProvider
     *
     * @param array $sessionOptions
     * @param array $expectedParameters
     * @param array $expectedOptions
     */
    public function testSingleConnectionCreation($sessionOptions, $expectedParameters, $expectedOptions = [])
    {
        $sessionOptions = new OptionsBag($sessionOptions);

        $factory = new PredisFactory();

        $client = $factory->create($sessionOptions);

        $this->assertSingleConnection($client, $expectedParameters);

        $this->assertOptions($client, $expectedOptions);
    }

    /**
     * @dataProvider clusterConnectionCreationProvider
     *
     * @param       $sessionOptions
     * @param       $expectedParameters
     * @param array $expectedOptions
     */
    public function testCluster($sessionOptions, $expectedParameters = [], $expectedOptions = [])
    {
        $sessionOptions = new OptionsBag($sessionOptions);

        $factory = new PredisFactory();

        $client = $factory->create($sessionOptions);

        $this->assertClusterConnection($client, $expectedParameters);

        $this->assertOptions($client, $expectedOptions);
    }

    private function assertClusterConnection(ClientInterface $client, $expected)
    {
        /** @var PredisCluster|StreamConnection[] $cluster */
        $cluster = $client->getConnection();
        $this->assertInstanceOf(PredisCluster::class, $cluster);
        foreach ($cluster as $connection) {
            $this->assertParameters($connection->getParameters(), $expected);
        }
    }

    private function assertSingleConnection(ClientInterface $client, $expected)
    {
        /** @var StreamConnection $connection */
        $connection = $client->getConnection();
        $this->assertInstanceOf(StreamConnection::class, $connection);
        $this->assertParameters($connection->getParameters(), $expected);
    }

    private function assertParameters(ParametersInterface $params, $expected)
    {
        // Taken from Predis\Connection\ParametersInterface
        $keys = ['scheme', 'host', 'port', 'path', 'alias', 'timeout', 'read_write_timeout', 'async_connect', 'tcp_nodelay', 'persistent', 'password', 'database'];

        foreach ($keys as $key) {
            if (isset($expected[$key])) {
                $this->assertEquals($expected[$key], $params->$key);
            }
        }
    }

    private function assertOptions(ClientInterface $client, $expected)
    {
        $options = $client->getOptions();

        /** @var KeyPrefixProcessor|null $prefix */
        $prefix = $options->prefix;
        if (isset($expected['prefix'])) {
            $this->assertInstanceOf(KeyPrefixProcessor::class, $prefix);
            $this->assertEquals($expected['prefix'], $prefix->getPrefix());
        } else {
            $this->assertNull($prefix);
        }
    }
}
