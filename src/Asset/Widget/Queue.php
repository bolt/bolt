<?php
namespace Bolt\Asset\Widget;

use Bolt\Asset\QueueInterface;
use Silex\Application;
use Bolt\Asset\Target;

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
        $javascript = $this->app['render']->render('widgetjavascript.twig', [
            'widget' => $widget
        ]);

        return $this->app['asset.injector']->inject((string) $javascript, Target::AFTER_BODY_JS, $html);
    }
}
