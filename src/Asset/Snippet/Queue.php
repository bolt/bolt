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
        /** @var Snippet $asset */
        foreach ($this->queue as $key => $asset) {
            if ($asset->getZone() === Zone::get($request)) {
                $this->injector->inject($asset, $asset->getLocation(), $response);
            }
            unset($this->queue[$key]);
        }

        // Conditionally add jQuery
        $this->addJquery($request, $response);
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
}
