<?php

namespace Bolt\EventListener;

use Bolt\Asset\Snippet\Queue;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Canonical;
use Bolt\Config;
use Bolt\Controller\Zone;
use Bolt\Render;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SnippetListener implements EventSubscriberInterface
{
    /** @var Queue */
    protected $queue;
    /** @var Canonical */
    protected $canonical;
    /** @var Packages */
    protected $packages;
    /** @var Config */
    protected $config;
    /** @var Render */
    protected $render;

    /**
     * Constructor.
     *
     * @param Queue     $queue
     * @param Canonical $canonical
     * @param Packages  $packages
     * @param Config    $config
     * @param Render    $render
     */
    public function __construct(Queue $queue, Canonical $canonical, Packages $packages, Config $config, Render $render)
    {
        $this->queue = $queue;
        $this->canonical = $canonical;
        $this->packages = $packages;
        $this->config = $config;
        $this->render = $render;
    }

    /**
     * Callback for response event.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (Zone::isAsync($request)) {
            return;
        }

        $response = $event->getResponse();
        if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
            return;
        }

        if (!$event->getRequest()->isXmlHttpRequest()) {
            $this->addSnippets();
        }

        $this->render->postProcess($request, $response);
    }

    /**
     * Add base snippets to the response.
     */
    protected function addSnippets()
    {
        $generatorSnippet = (new Snippet())
            ->setLocation(Target::END_OF_HEAD)
            ->setCallback('<meta name="generator" content="Bolt">')
        ;
        $this->queue->add($generatorSnippet);

        $canonicalUrl = $this->canonical->getUrl();
        $canonicalSnippet = (new Snippet())
            ->setLocation(Target::END_OF_HEAD)
            ->setCallback($this->encode('<link rel="canonical" href="%s">', $canonicalUrl))
        ;
        $this->queue->add($canonicalSnippet);

        if ($favicon = $this->config->get('general/favicon')) {
            $faviconUrl = $this->packages->getUrl($favicon, 'theme');
            $faviconSnippet = (new Snippet())
                ->setLocation(Target::END_OF_HEAD)
                ->setCallback($this->encode('<link rel="shortcut icon" href="%s">', $faviconUrl))
            ;
            $this->queue->add($faviconSnippet);
        }
    }

    /**
     * Encode the snippet string and make it HTML safe.
     *
     * @param string $str
     *
     * @return string
     */
    protected function encode($str)
    {
        $args = func_get_args();
        array_shift($args);
        $args = array_map(
            function ($str) {
                return htmlspecialchars($str, ENT_QUOTES);
            },
            $args
        );
        array_unshift($args, $str);

        return call_user_func_array('sprintf', $args);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }
}
