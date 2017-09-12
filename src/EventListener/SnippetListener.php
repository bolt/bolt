<?php

namespace Bolt\EventListener;

use Bolt\Asset\QueueInterface;
use Bolt\Asset\Snippet\Queue as SnippetQueue;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Config;
use Bolt\Controller\Zone;
use Bolt\Routing\Canonical;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SnippetListener implements EventSubscriberInterface
{
    /** @var QueueInterface[] */
    protected $queues;
    /** @var Canonical */
    protected $canonical;
    /** @var Packages */
    protected $packages;
    /** @var Config */
    protected $config;

    /**
     * Constructor.
     *
     * @param QueueInterface[] $queues
     * @param Canonical        $canonical
     * @param Packages         $packages
     * @param Config           $config
     */
    public function __construct(array $queues, Canonical $canonical, Packages $packages, Config $config)
    {
        $this->queues = $queues;
        $this->canonical = $canonical;
        $this->packages = $packages;
        $this->config = $config;
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
        if (Zone::isAsync($request) || $request->isXmlHttpRequest()) {
            return;
        }

        $response = $event->getResponse();
        if ($response->isRedirection() || $response->isEmpty() || $response->isInformational()) {
            return;
        }
        if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
            return;
        }

        $this->addSnippets();

        foreach ($this->queues as $queue) {
            $queue->process($request, $response);
        }
    }

    /**
     * Add base snippets to the response.
     */
    protected function addSnippets()
    {
        $queue = null;
        foreach ($this->queues as $q) {
            if ($q instanceof SnippetQueue) {
                $queue = $q;
                break;
            }
        }
        if (!$queue) {
            return;
        }

        $generatorSnippet = (new Snippet())
            ->setLocation(Target::END_OF_HEAD)
            ->setCallback('<meta name="generator" content="Bolt">')
        ;
        $queue->add($generatorSnippet);

        if ($canonicalUrl = $this->canonical->getUrl()) {
            $canonicalSnippet = (new Snippet())
                ->setLocation(Target::END_OF_HEAD)
                ->setCallback($this->encode('<link rel="canonical" href="%s">', $canonicalUrl))
            ;
            $queue->add($canonicalSnippet);
        }

        if ($favicon = $this->config->get('general/favicon')) {
            $faviconUrl = $this->packages->getUrl($favicon, 'theme');
            $faviconSnippet = (new Snippet())
                ->setLocation(Target::END_OF_HEAD)
                ->setCallback($this->encode('<link rel="shortcut icon" href="%s">', $faviconUrl))
            ;
            $queue->add($faviconSnippet);
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
