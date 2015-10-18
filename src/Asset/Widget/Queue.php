<?php
namespace Bolt\Asset\Widget;

use Bolt\Asset\QueueInterface;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Silex\Application;

/**
 * Widget queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue implements QueueInterface
{
    /** @var Widget[] Queue with snippets of HTML to insert. */
    protected $queue = [];

    /** @var \Silex\Application */
    private $app;
    /** @var boolean */
    private $deferAdded;

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
     * Add a wiget to the queue.
     *
     * @param Widget $widget
     */
    public function add(Widget $widget)
    {
        $this->queue[] = $widget;
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
        // Process the widgets in the queue.
        foreach ($this->queue as $widget) {
            if ($widget->getType() === 'frontend' && $widget->isDeferred()) {
                $html = $this->addDeferredJavaScript($widget, $html);
            }
        }

        return $html;
    }

    /**
     * Get the queued widgets.
     *
     * @return \Bolt\Asset\Widget\Widget[]
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Render a location's widget.
     *
     * @param string $type
     * @param string $location
     *
     * @return string|null
     */
    public function render($type, $location)
    {
        $html = null;
        foreach ($this->queue as $widget) {
            if ($widget->getType() === $type && $widget->getLocation() === $location) {
                $html .= $this->getHtml($widget);
            }
        }

        return $html;
    }

    /**
     * Get the HTML content from the widget.
     *
     * @param Widget $widget
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getHtml(Widget $widget)
    {
        $e = null;
        set_error_handler(function ($errno, $errstr) use (&$e) {
            return $e = new \Exception($errstr, $errno);
        });

        // Get the HTML from object cast and rethrow an exception if present
        $html = (string) $widget;

        restore_error_handler();

        if ($e) {
            throw $e;
        }

        return $html;
    }

    /**
     * Insert a snippet of Javascript to fetch the actual widget's contents.
     *
     * @param Widget $widget
     * @param string $html
     *
     * @return string
     */
    protected function addDeferredJavaScript(Widget $widget, $html)
    {
        if ($this->deferAdded) {
            return $html;
        }

        $javaScript = $this->app['render']->render('widgetjavascript.twig', [
            'widget' => $widget
        ]);
        $snippet = new Snippet(Target::AFTER_BODY_JS, (string) $javaScript);
        $this->deferAdded = true;

        return $this->app['asset.injector']->inject($snippet, Target::AFTER_BODY_JS, $html);
    }
}
