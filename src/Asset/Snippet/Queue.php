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

    /** @var array */
    private $matchedComments;

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
        // First, gather all html <!-- comments -->, because they shouldn't be
        // considered for replacements. We use a callback, so we can fill our
        // $this->matchedComments array
        preg_replace_callback('/<!--(.*)-->/Uis', [$this, 'pregCallback'], $response->getContent());

        /** @var Snippet $asset */
        foreach ($this->queue as $key => $asset) {
            if ($asset->getZone() === Zone::get($request)) {
                $this->injector->inject($asset, $asset->getLocation(), $response);
            }
            unset($this->queue[$key]);
        }

        // Finally, replace back ###comment### with its original comment.
        if (!empty($this->matchedComments)) {
            $html = preg_replace(array_keys($this->matchedComments), $this->matchedComments, $response->getContent(), 1);
            $response->setContent($html);
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

    /**
     * Callback method to identify comments and store them in the
     * matchedComments array.
     *
     * These will be put back after the replacements on the HTML are finished.
     *
     * @param string $c
     *
     * @return string The key under which the comment is stored
     */
    private function pregCallback($c)
    {
        $key = '###bolt-comment-' . count($this->matchedComments) . '###';
        // Add it to the array of matched comments.
        $this->matchedComments['/' . $key . '/'] = $c[0];

        return $key;
    }
}
