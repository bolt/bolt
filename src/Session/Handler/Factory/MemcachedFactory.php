<?php

namespace Bolt\Session\Handler\Factory;

use Bolt\Helpers\Deprecated;
use Bolt\Session\IniBag;
use Bolt\Session\OptionsBag;
use InvalidArgumentException;
use Memcached;
use RuntimeException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Factory for creating Memcached instances from Session options and memcached ini options.
 *
 * We will support v2.0 of the extension until support for PHP 5 is dropped.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MemcachedFactory extends AbstractFactory
{
    /** @var ParameterBag */
    protected $ini;
    /** @var string */
    protected $class;

    /**
     * Constructor.
     *
     * @param ParameterBag|null $ini   ini values. Mostly for tests.
     * @param string|null       $class Memcached class name. Mostly for tests.
     */
    public function __construct(ParameterBag $ini = null, $class = Memcached::class)
    {
        if (!extension_loaded('memcached')) {
            throw new RuntimeException('Unable to use "memcached" session handler as memcached extension is not installed and enabled.');
        }
        if (version_compare(phpversion('memcached'), '2.0.0', '<')) {
            throw new RuntimeException('Unable to use "memcached" session handler as memcached extension is too old. Version 2.0.0 or higher is required.');
        }

        $this->ini = $ini ?: new IniBag('memcached', 'sess_');
        $this->class = $class ?: Memcached::class;

        if (!is_a($this->class, Memcached::class, true)) {
            throw new InvalidArgumentException(sprintf('Class name "%s" is not Memcached or a subclass of Memcached.', $this->class));
        }
    }

    /**
     * Creates a Memcached instance from the session options.
     *
     * @param OptionsBag $sessionOptions
     *
     * @return Memcached
     */
    public function create(OptionsBag $sessionOptions)
    {
        $options = $this->parseOptions($sessionOptions);

        $connections = $this->parse($sessionOptions);

        $persistentId = null;
        if ($options['persistent']) {
            $persistentId = $this->parsePersistentId($connections, $options, $sessionOptions);
        }

        $class = $this->class;
        /** @var Memcached $memcached */
        $memcached = new $class(
            $persistentId,
            function (Memcached $memcached) use ($connections, $options) {
                $this->configure($memcached, $connections, $options);
            }
        );

        return $memcached;
    }

    /**
     * Configure a new Memcached instance. This isn't needed for existing persisted connections.
     *
     * @param Memcached  $memcached
     * @param array      $connections
     * @param OptionsBag $options
     */
    protected function configure(Memcached $memcached, array $connections, OptionsBag $options)
    {
        $binary = $options->getBoolean('binary_protocol');
        $needsAuth = $options->get('username') && $options->get('password');

        if ($needsAuth) {
            // SASL is only supported with binary protocol,
            // just enable it instead of throwing exception.
            $binary = true;

            if (!constant($this->class . '::HAVE_SASL')) {
                throw new RuntimeException('memcached extension needs to be built with SASL support to use username and password');
            }
        }

        $memcached->setOptions([
            Memcached::OPT_BINARY_PROTOCOL        => $binary,
            Memcached::OPT_LIBKETAMA_COMPATIBLE   => $options->getBoolean('consistent_hash'),
            Memcached::OPT_SERVER_FAILURE_LIMIT   => $options->getInt('server_failure_limit'),
            Memcached::OPT_NUMBER_OF_REPLICAS     => $options->getInt('number_of_replicas'),
            Memcached::OPT_RANDOMIZE_REPLICA_READ => $options->getBoolean('randomize_replica_read'),
            Memcached::OPT_REMOVE_FAILED_SERVERS  => $options->getBoolean('remove_failed_servers'),
            Memcached::OPT_CONNECT_TIMEOUT        => $options->getInt('connect_timeout'),
        ]);

        if ($needsAuth) {
            $memcached->setSaslAuthData($options->get('username'), $options->get('password'));
        }

        foreach ($connections as $conn) {
            $memcached->addServer($conn['host'], $conn['port'], $conn['weight']);
        }
    }

    /**
     * Parse Memcached options from session options and from ini.
     *
     * @param OptionsBag $sessionOptions
     *
     * @return OptionsBag
     */
    protected function parseOptions(OptionsBag $sessionOptions)
    {
        $options = new OptionsBag($sessionOptions->get('options', []));

        $iniKeys = [
            'persistent'             => 'bool',
            'binary_protocol'        => 'bool',
            'consistent_hash'        => 'bool',
            'server_failure_limit'   => 'int',
            'remove_failed_servers'  => 'bool',
            'randomize_replica_read' => 'bool',
            'number_of_replicas'     => 'int',
            'connect_timeout'        => 'int',
            'sasl_username'          => 'string',
            'sasl_password'          => 'string',
            'prefix'                 => 'string',
        ];
        $v2IniKeys = [
            'remove_failed_servers' => ['remove_failed', 'bool'],
            'binary_protocol'       => ['binary', 'bool'],
        ];

        // If user specified v2 option, warn and replace with new name
        foreach ($v2IniKeys as $new => list($old, $type)) {
            if ($options->has($old)) {
                Deprecated::warn("Memcached option \"$old\"", 3.3, "Use \"$new\" instead.");

                if (!$options->has($new)) {
                    $options->set($new, $options->get($old));
                }

                $options->remove($old);
            }
        }

        // Merge ini values in as defaults.
        foreach ($iniKeys as $key => $type) {
            if (!$options->has($key) && $this->ini->has($key)) {
                if ($type === 'bool') {
                    $value = $this->ini->getBoolean($key);
                } elseif ($type === 'int') {
                    $value = $this->ini->getInt($key);
                } else {
                    $value = $this->ini->get($key);
                }
                $options[$key] = $value;
            }
        }

        // Merge v2 ini values in as defaults.
        foreach ($v2IniKeys as $new => list($old, $type)) {
            if (!$options->has($new) && $this->ini->has($old)) {
                if ($type === 'bool') {
                    $value = $this->ini->getBoolean($old);
                } elseif ($type === 'int') {
                    $value = $this->ini->getInt($old);
                } else {
                    $value = $this->ini->get($old);
                }
                $options[$new] = $value;
            }
        }

        // Convert sasl_username/sasl_password to username/password
        if (!$options->has('username')) {
            $options['username'] = $options->get('sasl_username');
        }
        if (!$options->has('password')) {
            $options['password'] = $options->get('sasl_password');
        }
        $options->remove('sasl_username');
        $options->remove('sasl_password');

        return $options;
    }

    /**
     * Parse the Persistent ID from save_path and sets it on options.
     *
     * 1. Given in save_path "PERSISTENT=foo"
     * 2. Given in options via "persistent_id"
     * 3. Determined automatically based on current connections and options.
     *
     * @param OptionsBag[] $connections
     * @param OptionsBag   $options
     * @param OptionsBag   $sessionOptions
     *
     * @return string
     */
    protected function parsePersistentId(array $connections, OptionsBag $options, OptionsBag $sessionOptions)
    {
        // Try based on save_path.
        $savePath = $sessionOptions['save_path'];
        $savePath = trim($savePath); // Just in case.
        if ($savePath) {
            // v3.0 uses save path for ID (from what I can tell)
            $persistentId = 'memc-session:' . $savePath;

            // v2.0 uses "PERSISTENT=foo servers", so parse that.
            if (strpos($savePath, 'PERSISTENT=') === 0) {
                $end = strpos($savePath, ' ');
                if ($end === false) {
                    throw new InvalidArgumentException('Unable to parse session save_path');
                }

                $persistentId = substr($savePath, 11, $end - 11);
            }

            return $persistentId;
        }

        // Try based on specified value in options.
        if ($options->has('persistent_id')) {
            return $options['persistent_id'];
        }

        // Determine automatically.
        $hashParts = [];

        foreach ($connections as $conn) {
            $hashParts[] = sprintf('%s:%s:%s', $conn['host'], $conn['port'], $conn['weight']);
        }
        foreach ($options as $key => $value) {
            $hashParts[] = $key . ':' . $value;
        }

        $hash = hash('sha256', implode(',', $hashParts));

        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionsFromSavePath($savePath)
    {
        // v2.0 uses "PERSISTENT=foo servers", so remove that.
        if (strpos($savePath, 'PERSISTENT=') === 0) {
            $end = strpos($savePath, ' ');
            if ($end === false) {
                throw new InvalidArgumentException('Unable to parse session save_path');
            }

            $savePath = substr($savePath, $end + 1);
        }

        return parent::parseConnectionsFromSavePath($savePath);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionItemFromSavePath($path)
    {
        $connection = trim($path);

        $parts = explode(':', $connection);

        $uri = $this->parseUri($parts[0]);

        if (!$uri->getHost() && !$uri->getPath()) {
            throw new InvalidArgumentException('Failed to parse session save_path');
        }

        if ($uri->getPath()) {
            $host = $uri->getPath();
            $port = 0;
        } else {
            $host = $uri->getHost();
            $port = (int) isset($parts[1]) ? $parts[1] : 11211;
        }
        $weight = (int) isset($parts[2]) ? $parts[2] : 1;

        return new OptionsBag([
            'host'   => $host,
            'port'   => $port,
            'weight' => $weight,
        ]);
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
            'host'   => $item->get('host') ?: '127.0.0.1',
            'port'   => $item->getInt('port') ?: 11211,
            'weight' => $item->getInt('weight', 1),
        ]);

        if ($item['path']) {
            $conn['host'] = $item['path'];
            $conn['port'] = 0;
        }

        return $conn;
    }
}
