<?php
namespace Bolt\Asset\Snippet;

use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Target;
use Bolt\Config;
use Bolt\Configuration\ResourceManager;
use Bolt\Controller\Zone;
use Doctrine\Common\Cache\CacheProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;
    /** @var \Psr\Log\LoggerInterface */
    protected $loggerSystem;

    /** @var array */
    private $matchedComments;

    /**
     * Constructor.
     *
     * @param Injector        $injector
     * @param CacheProvider   $cache
     * @param Config          $config
     * @param ResourceManager $resources
     * @param RequestStack    $requestStack
     * @param LoggerInterface $loggerSystem
     */
    public function __construct(
        Injector $injector,
        CacheProvider $cache,
        Config $config,
        ResourceManager $resources,
        RequestStack $requestStack,
        LoggerInterface $loggerSystem
    ) {
        $this->injector = $injector;
        $this->cache = $cache;
        $this->config = $config;
        $this->resources = $resources;
        $this->requestStack = $requestStack;
        $this->loggerSystem = $loggerSystem;
    }

    /**
     * Insert a snippet. And by 'insert' we actually mean 'add it to the queue,
     * to be processed later'.
     *
     * @param string          $location
     * @param callable|string $callback
     * @param string          $extensionName
     * @param array|null      $callbackArguments
     */
    public function add($location, $callback, $extensionName = 'core', array $callbackArguments = [])
    {
        $callback = $this->getCallableResult($extensionName, $callback, $callbackArguments);
        $this->queue[] = (new Snippet())
            ->setLocation($location)
            ->setCallback($callback)
            ->setExtension($extensionName)
            ->setCallbackArguments($callbackArguments)
        ;
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
    public function process($html)
    {
        // First, gather all html <!-- comments -->, because they shouldn't be
        // considered for replacements. We use a callback, so we can fill our
        // $this->matchedComments array
        $html = preg_replace_callback('/<!--(.*)-->/Uis', [$this, 'pregCallback'], $html);

        /** @var Snippet $asset */
        foreach ($this->queue as $key => $asset) {
            $html = $this->injector->inject($asset, $asset->getLocation(), $html);
            unset($this->queue[$key]);
        }

        // Conditionally add jQuery
        $html = $this->addJquery($html);

        // Finally, replace back ###comment### with its original comment.
        if (!empty($this->matchedComments)) {
            $html = preg_replace(array_keys($this->matchedComments), $this->matchedComments, $html, 1);
        }

        return $html;
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
     * @param string $html
     *
     * @return string HTML
     */
    protected function addJquery($html)
    {
        if (!$this->config->get('general/add_jquery', false) &&
            !$this->config->get('theme/add_jquery', false)) {
            return $html;
        }

        $zone = Zone::FRONTEND;
        if ($request = $this->requestStack->getCurrentRequest()) {
            $zone = Zone::get($request);
        }

        $regex = '/<script(.*)jquery(-latest|-[0-9\.]*)?(\.min)?\.js/';
        if ($zone === Zone::FRONTEND && !preg_match($regex, $html)) {
            $jqueryfile = $this->resources->getPath('app/view/js/jquery-2.1.4.min.js');
            $asset = (new Snippet())
                ->setLocation(Target::BEFORE_JS)
                ->setCallback('<script src="' . $jqueryfile . '"></script>')
            ;
            $html = $this->injector->inject($asset, $asset->getLocation(), $html);
        }

        return $html;
    }

    /**
     * Get the output from the callback.
     *
     * @param string          $extensionName
     * @param callable|string $callback
     * @param array           $parameters
     *
     * @return string
     */
    private function getCallableResult($extensionName, $callback, array $parameters)
    {
        if ($extensionName === 'core' && is_callable($callback)) {
            // Snippet is a callback in the 'global scope'
            return call_user_func_array($callback, (array) $parameters);
        } elseif ($callable = $this->getCallable($extensionName, $callback)) {
            // Snippet is defined in the extension itself.
            return call_user_func_array($callable, (array) $parameters);
        } elseif (is_string($callback) || $callback instanceof \Twig_Markup) {
            // Insert the 'callback' as a string.
            return (string) $callback;
        }

        try {
            $this->loggerSystem->critical(sprintf('Snippet loading failed for %s with callable %s', $extensionName, serialize($callback)), ['context' => 'extensions']);
        } catch (\Exception $e) {
            $this->loggerSystem->critical(sprintf('Snippet loading failed for %s with an unknown callback.', $extensionName), ['context' => 'extensions']);
        }

        return '';
    }

    /**
     * Check for a valid snippet callback.
     *
     * @param string          $extensionName
     * @param callable|string $callback
     *
     * @return callable|null
     */
    private function getCallable($extensionName, $callback)
    {
        if (is_callable($callback)) {
            return $callback;
        } elseif (is_callable([$extensionName, $callback])) {
            return [$extensionName, $callback];
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
