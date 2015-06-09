<?php
namespace Bolt\Assets\Snippets;

use Bolt\Assets\Target;
use Bolt\Controller\Zone;
use Silex\Application;

/**
 * Snippet queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue
{
    /** @var boolean Whether to add jQuery to the HTML */
    protected $addJquery;
    /** @var array Queue with snippets of HTML to insert. */
    protected $queue = [];

    /** @var \Silex\Application */
    private $app;
    /** @var string */
    private $matchedComments;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Insert a snippet. And by 'insert' we actually mean 'add it to the queue,
     * to be processed later'.
     *
     * @param string          $location
     * @param callable|string $callback
     * @param string          $extensionName
     * @param array           $extraParameters
     */
    public function add($location, $callback, $extensionName = 'core', array $extraParameters = [])
    {
        $this->queue[] = new Snippet($location, $callback, $extensionName, $extraParameters);
    }

    /**
     * Clears the snippet queue.
     */
    public function clear()
    {
        $this->queue = [];
    }

    /**
     * Extensions::processSnippetQueue()
     *
     * @param string $html
     *
     * @return string
     */
    public function process($html)
    {
        // First, gather all html <!-- comments -->, because they shouldn't be
        // considered for replacements. We use a callback, so we can fill our
        // $this->matchedComments array
        $html = preg_replace_callback('/<!--(.*)-->/Uis', [$this, 'pregCallback'], $html);

        // Replace the snippets in the queue.
        $html = $this->processInternal($html);

        // Conditionally add jQuery
        $html = $this->addJquery($html);

        // Finally, replace back ###comment### with its original comment.
        if (!empty($this->matchedComments)) {
            $html = preg_replace(array_keys($this->matchedComments), $this->matchedComments, $html, 1);
        }

        return $html;
    }

    /**
     * Replace the snippets in the queue.
     *
     * @param string $html
     *
     * @return string
     */
    protected function processInternal($html)
    {
        $functionMap = $this->getMap();

        foreach ($this->queue as $item) {
            $snippet = $this->getCallbackHtml($item);

            $location = $item['location'];
            if (isset($functionMap[$location])) {
                $this->app['assets.injector']->{$functionMap[$location]}($snippetHtml, $html);
            } else {
                $html .= "$snippet\n";
            }
        }

        return $html;
    }

    /**
     * Get the snippet, either by using a callback function, or else use the
     * passed string as-is.
     *
     * @param array $item
     *
     * @return string
     */
    protected function getCallbackHtml(array $item)
    {
        if (($item['extension'] !== 'core') && $callable = $this->getExtensionCallable($item)) {
            // Snippet is defined in the extension itself.
            return call_user_func_array($callable, (array) $item['extraparameters']);
        } elseif (function_exists($item['callback'])) {
            // Snippet is a callback in the 'global scope'
            return call_user_func($item['callback'], $this->app, $item['extraparameters']);
        } else {
            // Insert the 'callback' as a string.
            return $item['callback'];
        }
    }

    /**
     * Check for an enabled extension with a valid snippet callback.
     *
     * @param array $item
     *
     * @return callable|null
     */
    private function getExtensionCallable(array $item)
    {
        $extension = $this->app['extensions']->getInitialized($item['extension']);

        if (method_exists($extension, $item['callback'])) {
            return [$extension, $item['callback']];
        }
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
        $zone = Zone::FRONTEND;
        /** @var RequestStack $requestStack */
        $requestStack = $this->app['request_stack'];
        if ($request = $requestStack->getCurrentRequest()) {
            $zone = Zone::get($request);
        }

        $regex = '/<script(.*)jquery(-latest|-[0-9\.]*)?(\.min)?\.js/';
        if ($this->addJquery && $zone === Zone::FRONTEND && !preg_match($regex, $html)) {
            $jqueryfile = $this->app['resources']->getPath('app/view/js/jquery-1.11.2.min.js');
            $html = $this->app['assets.injector']->beforeJs('<script src="' . $jqueryfile . '"></script>', $html);
        }

        return $html;
    }

    /**
     * Get a map of function names to locations.
     *
     * @return array
     */
    protected function getMap()
    {
        return [
            Target::END_OF_HEAD      => 'headTagEnd',
            Target::AFTER_HEAD_JS    => 'headTagEnd', // same as end of head because we cheat a little
            Target::AFTER_HEAD_CSS   => 'headTagEnd', // same as end of head because we cheat a little
            Target::AFTER_HEAD_META  => 'headTagEnd', // same as end of head because meta tags are unordered

            Target::BEFORE_CSS       => 'cssTagsBefore',
            Target::BEFORE_JS        => 'jsTagsBefore',
            Target::AFTER_META       => 'metaTagsAfter',
            Target::AFTER_CSS        => 'cssTagsAfter',
            Target::AFTER_JS         => 'jsTagsAfter',

            Target::START_OF_HEAD    => 'headTagStart',
            Target::BEFORE_HEAD_JS   => 'headTagStart', // same as start of head because we cheat a little
            Target::BEFORE_HEAD_CSS  => 'headTagStart', // same as start of head because we cheat a little
            Target::BEFORE_HEAD_META => 'headTagStart', // same as start of head because meta tags are unordered

            Target::START_OF_BODY    => 'bodyTagStart',
            Target::BEFORE_BODY_JS   => 'bodyTagStart', // same as start of body because we cheat a little
            Target::BEFORE_BODY_CSS  => 'bodyTagStart', // same as start of body because we cheat a little

            Target::END_OF_BODY      => 'bodyTagEnd',
            Target::AFTER_BODY_JS    => 'bodyTagEnd',   // same as end of body because we cheat a little
            Target::AFTER_BODY_CSS   => 'bodyTagEnd',   // same as end of body because we cheat a little

            Target::END_OF_HTML      => 'htmlTagEnd',
        ];
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
