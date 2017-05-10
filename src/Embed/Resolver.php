<?php

namespace Bolt\Embed;

use Bolt\Exception\EmbedResolverException;
use Embed\Exceptions\EmbedException;
use Embed\Exceptions\InvalidUrlException;
use Embed\Http\Url;
use Psr\Http\Message\UriInterface;

/**
 * Embed resolver service.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Resolver
{
    /** @var callable */
    private $embedFactory;

    /**
     * Constructor.
     *
     * @param callable $embedFactory
     */
    public function __construct(callable $embedFactory)
    {
        $this->embedFactory = $embedFactory;
    }

    /**
     * Return embed information from a given URL.
     *
     * @param UriInterface $url
     * @param string       $providerName
     *
     * @return array
     */
    public function embed(UriInterface $url, $providerName)
    {
        $adapter = $this->getUrlAdapter($url);
        $providers = $adapter->getProviders();
        if (!isset($providers[$providerName])) {
            return [];
        }
        /** @var \Embed\Providers\Provider $provider */
        $provider = $providers[$providerName];

        return $provider->getBag()->getAll();
    }

    /**
     * Return the best embeded image from a given URL.
     *
     * @param UriInterface $url
     *
     * @return string
     */
    public function image(UriInterface $url)
    {
        $adapter = $this->getUrlAdapter($url);

        return $adapter->getImage();
    }

    /**
     * Return embed images from a given URL.
     *
     * @param UriInterface $url
     *
     * @return array Values: url, width, height, size, mime (array)
     */
    public function images(UriInterface $url)
    {
        $adapter = $this->getUrlAdapter($url);

        return $adapter->getImages();
    }

    /**
     * Return an API adapter matching the pattern of the given URL.
     *
     * @param UriInterface $url
     *
     * @return \Embed\Adapters\Adapter
     */
    private function getUrlAdapter(UriInterface $url)
    {
        $embedFactory = $this->embedFactory;
        $url = Url::create((string) $url);

        try {
            $adapter = $embedFactory($url);
        } catch (InvalidUrlException $e) {
            throw new EmbedResolverException($e->getMessage(), 1, $e);
        } catch (EmbedException $e) {
            throw new EmbedResolverException('Provider exception occurred', 2, $e);
        }

        return $adapter;
    }
}
