<?php

namespace Bolt\Asset\Widget;

use Bolt\Asset\AssetSortTrait;
use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Bolt\Render;
use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Widget queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue implements QueueInterface
{
    use AssetSortTrait;

    /** @var WidgetAssetInterface[] Queue with snippets of HTML to insert. */
    protected $queue = [];
    /** @var \Bolt\Asset\Injector */
    protected $injector;
    /** @var \Doctrine\Common\Cache\CacheProvider */
    protected $cache;
    /** @var \Bolt\Render */
    protected $render;

    /** @var boolean */
    private $deferAdded;

    /**
     * Constructor.
     *
     * @param Injector      $injector
     * @param CacheProvider $cache
     * @param Render        $render
     */
    public function __construct(Injector $injector, CacheProvider $cache, Render $render)
    {
        $this->injector = $injector;
        $this->cache = $cache;
        $this->render = $render;
    }

    /**
     * Add a wiget to the queue.
     *
     * @param WidgetAssetInterface $widget
     */
    public function add(WidgetAssetInterface $widget)
    {
        $widget->setKey();
        $this->queue[$widget->getKey()] = $widget;
    }

    /**
     * Get a widget from the queue.
     *
     * @param string $key
     *
     * @return WidgetAssetInterface
     */
    public function get($key)
    {
        return $this->queue[$key];
    }

    /**
     * Get a rendered (and potentially cached) widget from the queue.
     *
     * @param string $key
     *
     * @return \Twig_Markup|string
     */
    public function getRendered($key)
    {
        return $this->getHtml($this->queue[$key]);
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
        /** @var WidgetAssetInterface $widget */
        foreach ($this->queue as $widget) {
            if ($widget->getZone() === Zone::FRONTEND && $widget->isDeferred()) {
                $this->addDeferredJavaScript($widget, $response);
            }
        }
    }

    /**
     * Get the queued widgets.
     *
     * @return WidgetAssetInterface[]
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the number of queued widgets.
     *
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return boolean
     */
    public function hasItemsInQueue($location, $zone = Zone::FRONTEND)
    {
        return (boolean) $this->countItemsInQueue($location, $zone);
    }

    /**
     * Get the number of queued widgets.
     *
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return boolean
     */
    public function countItemsInQueue($location, $zone = Zone::FRONTEND)
    {
        $count = 0;

        foreach ($this->queue as $widget) {
            if ($widget->getZone() === $zone && $widget->getLocation() === $location) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Render a location's widget.
     *
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return string|null
     */
    public function render($location, $zone = Zone::FRONTEND, $wrapperTemplate = 'widgetwrapper.twig')
    {
        $widgets = [];

        /** @var WidgetAssetInterface $widget */
        foreach ($this->sort($this->queue) as $widget) {
            if ($widget->getZone() === $zone && $widget->getLocation() === $location) {
                $widgets[] = [ 'object' => $widget, 'html' => $this->getHtml($widget) ];
            }
        }

        if (!empty($widgets)) {
            $twigvars = [ 'location' => $location, 'widgets' => $widgets ];
            $html = $this->render->render($wrapperTemplate, $twigvars);
        } else {
            $html = null;
        }

        return $html;
    }

    /**
     * Get the HTML content from the widget.
     *
     * @param WidgetAssetInterface $widget
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getHtml(WidgetAssetInterface $widget)
    {
        $key = 'widget_' . $widget->getKey();
        if ($html = $this->cache->fetch($key)) {
            return $html;
        }

        /** @var \Exception $e */
        $e = null;
        set_error_handler(
            function ($errno, $errstr) use (&$e) {
                return $e = new \Exception($errstr, $errno);
            }
        );

        // Get the HTML from object cast and rethrow an exception if present
        $html = (string) $widget;

        restore_error_handler();

        if ($e instanceof \Exception) {
            throw $e;
        }
        if ($widget->getCacheDuration() !== null) {
            $this->cache->save($key, $html, $widget->getCacheDuration());
        }

        return $html;
    }

    /**
     * Insert a snippet of Javascript to fetch the actual widget's contents.
     *
     * @param WidgetAssetInterface $widget
     * @param Response             $response
     */
    protected function addDeferredJavaScript(WidgetAssetInterface $widget, Response $response)
    {
        if ($this->deferAdded) {
            return;
        }

        $javaScript = $this->render->render(
            'widgetjavascript.twig',
            [
                'widget' => $widget,
            ]
        );
        $snippet = (new Snippet())
            ->setLocation(Target::AFTER_BODY_JS)
            ->setCallback((string) $javaScript)
        ;

        $this->deferAdded = true;

        $this->injector->inject($snippet, Target::AFTER_BODY_JS, $response);
    }
}
