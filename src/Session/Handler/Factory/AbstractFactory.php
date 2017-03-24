<?php

namespace Bolt\Session\Handler\Factory;

use Bolt\Helpers\Deprecated;
use Bolt\Session\OptionsBag;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Abstract Factory with common functions to help create connections to services.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class AbstractFactory
{
    /**
     * Parse the session options to connection parameters.
     *
     * @param OptionsBag $sessionOptions
     *
     * @return OptionsBag[]
     */
    public function parse(OptionsBag $sessionOptions)
    {
        if ($sessionOptions['save_path']) {
            return $this->parseConnectionsFromSavePath($sessionOptions['save_path']);
        }

        return $this->parseConnectionsFromOptions($sessionOptions);
    }

    /**
     * Parses session save_path into a list of connection parameters.
     *
     * @param string $savePath
     *
     * @return OptionsBag[]
     */
    protected function parseConnectionsFromSavePath($savePath)
    {
        $connections = (array) explode(',', $savePath);
        $connections = array_map('trim', $connections);

        return array_map([$this, 'parseConnectionItemFromSavePath'], $connections);
    }

    /**
     * @param string $path
     *
     * @return OptionsBag
     */
    abstract protected function parseConnectionItemFromSavePath($path);

    /**
     * Parses session options with array configuration into a list of connection parameters.
     *
     * @param OptionsBag $options
     *
     * @return OptionsBag[]
     */
    protected function parseConnectionsFromOptions(OptionsBag $options)
    {
        if ($options->has('connections')) {
            $connections = $options->get('connections');
        } elseif ($options->has('connection')) {
            $connections = [$options->get('connection')];
        } elseif ($options->has('host') || $options->has('port')) {
            Deprecated::warn('Specifying "host" and other options directly in session config', 3.3, 'Move them under the "connection" key.');

            $connections = [$options->all()];
        } else {
            $connections = [[]];
        }

        return array_map([$this, 'parseConnectionItemFromOptions'], $connections);
    }

    /**
     * @param array|string $item
     *
     * @return OptionsBag
     */
    abstract protected function parseConnectionItemFromOptions($item);

    /**
     * @param string $uri
     *
     * @return UriInterface
     */
    protected function parseUri($uri)
    {
        if ($uri && stripos($uri, 'unix://') === 0) {
            // parse_url() doesn't like "unix:///foo", but parses "unix:/foo" fine.
            $uri = str_ireplace('unix://', 'unix:', $uri);
        } elseif ($uri && $uri[0] !== '/' && !preg_match('#^\w+://?.+#', $uri)) {
            // If no scheme given and path exists and isn't absolute, assume tcp scheme is wanted.
            // This allows both "127.0.0.1" and "/foo.sock" to work without specifying scheme.
            // If non absolute path is wanted, specify the scheme: "unix://foo.sock"
            $uri = 'tcp://' . $uri;
        }

        $parsed = new Uri($uri);

        return $parsed;
    }

    /**
     * @param UriInterface $uri
     *
     * @return OptionsBag
     */
    protected function parseQuery(UriInterface $uri)
    {
        $query = \GuzzleHttp\Psr7\parse_query($uri->getQuery());

        return new OptionsBag($query);
    }
}
