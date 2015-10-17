<?php
namespace Bolt\Asset\Widget;

use Bolt\Asset\QueueInterface;
use Silex\Application;
use Bolt\Asset\Target;
use Bolt\Asset\Snippet\Snippet;

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
            if ($widget->getType() === 'frontend' && $widget->getDefer()) {
                $html = $this->addDeferredJavaScript($widget, $html);
            }
        }

        return $html;
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
                $html .= $widget->getContent();
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
     * Insert a snippet of Javascript to fetch the actual widget's contents.
     *
     * @param Widget $widget
     * @param string $html
     *
     * @return string
     */
    protected function addDeferredJavaScript(Widget $widget, $html)
    {
        $javaScript = $this->app['render']->render('widgetjavascript.twig', [
            'widget' => $widget
        ]);
        $snippet = new Snippet(Target::AFTER_BODY_JS, (string) $javaScript);

        return $this->app['asset.injector']->inject($snippet, Target::AFTER_BODY_JS, $html);
    }
}
