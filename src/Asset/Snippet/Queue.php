<?php

namespace Bolt\Asset\Snippet;

use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Controller\Zone;
use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Snippet queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue implements QueueInterface
{
    /** @var Snippet[] Queue with snippets of HTML to insert. */
    protected $queue = [];
    /** @var \Bolt\Asset\Injector */
    protected $injector;
    /** @var \Doctrine\Common\Cache\CacheProvider */
    protected $cache;

    /**
     * Constructor.
     *
     * @param Injector      $injector
     * @param CacheProvider $cache
     */
    public function __construct(
        Injector $injector,
        CacheProvider $cache
    ) {
        $this->injector = $injector;
        $this->cache = $cache;
    }

    /**
     * Insert a snippet. And by 'insert' we actually mean 'add it to the queue,
     * to be processed later'.
     *
     * @param SnippetAssetInterface $snippet
     */
    public function add(SnippetAssetInterface $snippet)
    {
        $this->queue[] = $snippet;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->queue = [];
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, Response $response)
    {
        /** @var Snippet $asset */
        foreach ($this->queue as $key => $asset) {
            if ($asset->getZone() === Zone::get($request)) {
                $this->injector->inject($asset, $asset->getLocation(), $response);
            }
            unset($this->queue[$key]);
        }
    }

    /**
     * Get the queued snippets.
     *
     * @return \Bolt\Asset\Snippet\Snippet[]
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
