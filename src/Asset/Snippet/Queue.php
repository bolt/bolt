<?php
namespace Bolt\Asset\Snippet;

use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Target;
use Bolt\Config;
use Bolt\Configuration\ResourceManager;
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
    /** @var boolean Whether to add jQuery to the HTML */
    protected $addJquery;
    /** @var Snippet[] Queue with snippets of HTML to insert. */
    protected $queue = [];
    /** @var \Bolt\Asset\Injector */
    protected $injector;
    /** @var \Doctrine\Common\Cache\CacheProvider */
    protected $cache;
    /** @var \Bolt\Config */
    protected $config;
    /** @var \Bolt\Configuration\ResourceManager */
    protected $resources;

    /** @var array */
    private $matchedComments;

    /**
     * Constructor.
     *
     * @param Injector        $injector
     * @param CacheProvider   $cache
     * @param Config          $config
     * @param ResourceManager $resources
     */
    public function __construct(
        Injector $injector,
        CacheProvider $cache,
        Config $config,
        ResourceManager $resources
    ) {
        $this->injector = $injector;
        $this->cache = $cache;
        $this->config = $config;
        $this->resources = $resources;
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

        // Conditionally add jQuery
        $this->addJquery($request, $response);

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
     * Insert jQuery, if it's not inserted already.
     *
     * Some of the patterns that 'match' are:
     * - jquery.js
     * - jquery.min.js
     * - jquery-latest.js
     * - jquery-latest.min.js
     * - jquery-1.8.2.min.js
     * - jquery-1.5.js
     *
     * @param Request  $request
     * @param Response $response
     */
    protected function addJquery(Request $request, Response $response)
    {
        if (!$this->config->get('general/add_jquery', false) &&
            !$this->config->get('theme/add_jquery', false)) {
            return;
        }

        if (Zone::isFrontend($request) === false) {
            return;
        }

        $html = $response->getContent();
        $regex = '/<script(.*)jquery(-latest|-[0-9\.]*)?(\.min)?\.js/';
        if (!preg_match($regex, $html)) {
            $jqueryfile = $this->resources->getPath('app/view/js/jquery-2.2.4.min.js');
            $asset = (new Snippet())
                ->setLocation(Target::BEFORE_JS)
                ->setCallback('<script src="' . $jqueryfile . '"></script>')
            ;
            $this->injector->inject($asset, $asset->getLocation(), $response);
        }
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
