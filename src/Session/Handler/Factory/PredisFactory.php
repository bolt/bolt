<?php

namespace Bolt\Session\Handler\Factory;

use Bolt\Helpers\Deprecated;
use Bolt\Session\OptionsBag;
use Predis;

/**
 * Factory for creating Predis instances from Session options.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class PredisFactory extends AbstractFactory
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!class_exists('Predis\Client')) {
            throw new \RuntimeException('Unable to use "predis" session handler as the Predis library is not installed.');
        }
    }

    /**
     * Creates a Predis instance from the session options.
     *
     * @param OptionsBag $sessionOptions
     *
     * @return Predis\Client
     */
    public function create(OptionsBag $sessionOptions)
    {
        $connections = $this->parse($sessionOptions);

        $options = $this->parseOptions($sessionOptions);

        return new Predis\Client($connections, $options);
    }

    /**
     * Parse Predis options from session options.
     *
     * @param OptionsBag $sessionOptions
     *
     * @return array
     */
    protected function parseOptions(OptionsBag $sessionOptions)
    {
        $options = $sessionOptions->get('options', []);

        if ($sessionOptions->get('prefix')) {
            Deprecated::warn('Specifying "prefix" directly in session config', 3.3, 'Move it under the "options" key.');

            $options['prefix'] = $sessionOptions->get('prefix');
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionsFromOptions(OptionsBag $options)
    {
        $connections = parent::parseConnectionsFromOptions($options);

        // If only one connection, don't give list to Predis since it will unnecessarily use cluster logic.
        if (count($connections) === 1 && array_key_exists(0, $connections)) {
            $connections = $connections[0];
        }

        return $connections;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionItemFromOptions($item)
    {
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionsFromSavePath($savePath)
    {
        throw new \InvalidArgumentException('Using "save_path" is unsupported with "predis" session handler.');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseConnectionItemFromSavePath($path)
    {
    }
}
