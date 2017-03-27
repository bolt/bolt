<?php

namespace Bolt\Session\Handler\Factory;

use Bolt\Session\OptionsBag;
use InvalidArgumentException;
use Memcache;
use RuntimeException;

/**
 * Factory for creating Memcache instances from Session options.
 *
 * @deprecated since 3.3, will be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MemcacheFactory extends AbstractFactory
{
    /** @var string */
    protected $class;

    /**
     * Constructor.
     *
     * @param string|null $class Memcache class name. Mostly for tests.
     */
    public function __construct($class = Memcache::class)
    {
        if (!extension_loaded('memcache')) {
            throw new RuntimeException('Unable to use "memcache" session handler as memcache extension is not installed and enabled. Install the extension or try the "memcached" session handler instead.');
        }

        $this->class = $class ?: Memcache::class;

        if (!is_a($this->class, Memcache::class, true)) {
            throw new InvalidArgumentException(sprintf('Class name "%s" is not Memcache or a subclass of Memcache.', $this->class));
        }
    }

    /**
     * Creates a Memcache instance from the session options.
     *
     * @param OptionsBag $options
     *
     * @return Memcache
     */
    public function create(OptionsBag $options)
    {
        $connections = $this->parse($options);

        $class = $this->class;
        $memcache = new $class();

        return $this->configure($memcache, $connections);
    }

    /**
     * @param Memcache $memcache
     * @param array    $connections
     *
     * @return Memcache
     */
    public function configure(Memcache $memcache, array $connections)
    {
        foreach ($connections as $conn) {
            $memcache->addServer($conn['host'], $conn['port'], $conn['persistent'], $conn['weight'], $conn['timeout'], $conn['retry_interval']);
        }

        return $memcache;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionItemFromSavePath($path)
    {
        $uri = $this->parseUri($path);
        $query = $this->parseQuery($uri);

        $conn = new OptionsBag([
            'weight'         => $query->getInt('weight', 1),
            'persistent'     => $query->getBoolean('persistent'),
            'timeout'        => $query->getInt('timeout', 1),
            'retry_interval' => $query->getInt('retry_interval', 0),
        ]);

        if ($uri->getHost()) {
            $conn['host'] = $uri->getHost();
            $conn['port'] = $uri->getPort() ?: 11211;
        } else { // unix path
            $conn['host'] = 'unix://' . $uri->getPath();
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

        $conn = new OptionsBag([
            'host'           => $item->get('host') ?: '127.0.0.1',
            'port'           => $item->getInt('port') ?: 11211,
            'weight'         => $item->getInt('weight', 1),
            'persistent'     => $item->getBoolean('persistent', false),
            'timeout'        => $item->getInt('timeout', 1),
            'retry_interval' => $item->getInt('retry_interval', 0),
        ]);

        if ($item['path']) {
            $conn['host'] = 'unix://' . $item['path'];
            $conn['port'] = 0;
        }

        return $conn;
    }
}
