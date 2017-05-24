<?php

namespace Bolt\Session\Handler\Factory;

use Bolt\Helpers\Deprecated;
use Bolt\Session\OptionsBag;
use InvalidArgumentException;
use Redis;
use RuntimeException;

/**
 * Factory for creating Redis instances from Session options.
 *
 * Note: This is only for the PHP extension, not Predis.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class RedisFactory extends AbstractFactory
{
    /** @var string */
    protected $class;

    /**
     * Constructor.
     *
     * @param string|null $class Redis class name. Mostly for tests.
     */
    public function __construct($class = Redis::class)
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Unable to use "redis" session handler as redis extension is not installed and enabled. Install the extension or use "predis" session handler instead.');
        }

        $this->class = $class ?: Redis::class;

        if (!is_a($this->class, Redis::class, true)) {
            throw new InvalidArgumentException(sprintf('Class name "%s" is not Redis or a subclass of Redis.', $this->class));
        }
    }

    /**
     * Creates a Redis instance from the session options.
     *
     * @param OptionsBag $sessionOptions
     *
     * @return Redis
     */
    public function create(OptionsBag $sessionOptions)
    {
        $connections = $this->parse($sessionOptions);

        $conn = $this->selectConnection($connections);

        $class = $this->class;
        $redis = new $class();

        return $this->configure($redis, $conn);
    }

    /**
     * Configure the Redis instance with the parsed connection parameters.
     *
     * @param Redis      $redis
     * @param OptionsBag $conn
     *
     * @return Redis
     */
    public function configure(Redis $redis, OptionsBag $conn)
    {
        if ($conn['persistent']) {
            $redis->pconnect($conn['host'], $conn['port'], $conn['timeout']);
        } else {
            $redis->connect($conn['host'], $conn['port'], $conn['timeout'], $conn['retry_interval']);
        }

        if ($conn['password']) {
            $redis->auth($conn['password']);
        }

        if ($conn['database'] >= 0) {
            $redis->select($conn['database']);
        }

        if ($conn['prefix']) {
            $redis->setOption(Redis::OPT_PREFIX, $conn['prefix']);
        }

        return $redis;
    }

    /**
     * Select connection randomly accounting for weight.
     *
     * @param array $connections
     *
     * @return OptionsBag
     */
    public function selectConnection(array $connections)
    {
        $weighted = [];

        foreach ($connections as $connection) {
            foreach (range(0, $connection['weight']) as $i) {
                $weighted[] = $connection;
            }
        }

        $index = mt_rand(0, count($weighted) - 1);

        return $weighted[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function parse(OptionsBag $sessionOptions)
    {
        $connections = parent::parse($sessionOptions);

        // Copy prefix/options.prefix to connections if it doesn't have one already
        $prefix = 'PHPREDIS_SESSION:';
        $options = new OptionsBag($sessionOptions->get('options', []));
        if ($options->has('prefix')) {
            $prefix = $options->get('prefix');
        } elseif ($sessionOptions->has('prefix')) {
            Deprecated::warn('Specifying "prefix" directly in session config', 3.3, 'Move it under the "options" key.');

            $prefix = $sessionOptions->get('prefix');
        }

        foreach ($connections as $connection) {
            if (!$connection['prefix']) {
                $connection['prefix'] = $prefix;
            }
        }

        return $connections;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionItemFromSavePath($path)
    {
        $uri = $this->parseUri($path);
        $query = $this->parseQuery($uri);

        $conn = new OptionsBag([
            'password'       => $query->get('auth'),
            'database'       => $query->getInt('database', 0),
            'weight'         => $query->getInt('weight', 1),
            'persistent'     => $query->getBoolean('persistent'),
            'prefix'         => $query->get('prefix'),
            'timeout'        => (float) $query->get('timeout', 86400.0),
            'retry_interval' => $query->getInt('retry_interval', 0),
        ]);

        if ($uri->getHost()) {
            $conn['host'] = $uri->getHost();
            $conn['port'] = $uri->getPort() ?: 6379;
        } else { // unix path
            $conn['host'] = $uri->getPath();
            $conn['port'] = 0;
        }

        if ((!$conn['host'] && !$conn['path']) || $conn['weight'] <= 0 || $conn['timeout'] <= 0) {
            throw new InvalidArgumentException('Failed to parse session save_path');
        }

        return $conn;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionItemFromOptions($item)
    {
        if (is_string($item)) {
            $uri = $this->parseUri($item);
            $item = [
                'host' => $uri->getHost(),
                'port' => $uri->getPort(),
                'path' => $uri->getPath(),
            ];
        }

        $item = new OptionsBag($item);

        if ($prefix = $item->get('prefix')) {
            Deprecated::warn('Specifying "prefix" under the "connection(s)" key', 3.3, 'Move it under the "options" key.');
        }

        $conn = new OptionsBag([
            'host'           => $item->get('host') ?: '127.0.0.1',
            'port'           => $item->getInt('port') ?: 6379,
            'persistent'     => $item->getBoolean('persistent', false),
            'timeout'        => (float) $item->get('timeout', 86400.0),
            'retry_interval' => $item->getInt('retry_interval', 0),
            'weight'         => $item->getInt('weight', 1),
            'database'       => $item->getInt('database', 0),
            'prefix'         => $prefix,
            'password'       => $item->get('password'),
        ]);

        if ($item['path']) {
            $conn['host'] = $item['path'];
            $conn['port'] = 0;
        }

        if ($item['auth']) { // Not sure if needed for BC
            Deprecated::warn('Connection key "auth"', 3.3, 'Use "password" instead.');

            $conn['password'] = $item['auth'];
        }

        return $conn;
    }
}
