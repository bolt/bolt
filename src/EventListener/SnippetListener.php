<?php

namespace Bolt\EventListener;

use Bolt\Asset\Snippet\Queue;
use Bolt\Asset\Target;
use Bolt\Config;
use Bolt\Configuration\ResourceManager;
use Bolt\Controller\Zone;
use Bolt\Render;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SnippetListener implements EventSubscriberInterface
{
    /** @var Queue */
    protected $queue;
    /** @var Config */
    protected $config;
    /** @var ResourceManager */
    protected $resources;
    /** @var Render */
    protected $render;

    /**
     * SnippetListener constructor.
     *
     * @param Queue           $queue
     * @param Config          $config
     * @param ResourceManager $resources
     * @param Render          $render
     */
    public function __construct(Queue $queue, Config $config, ResourceManager $resources, Render $render)
    {
        $this->queue = $queue;
        $this->config = $config;
        $this->resources = $resources;
        $this->render = $render;
    }

    /**
     * Callback for reponse event.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (Zone::isAsync($event->getRequest())) {
            return;
        }

        $response = $event->getResponse();
        if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
            return;
        }

        if (!$event->getRequest()->isXmlHttpRequest()) {
            $this->addSnippets();
        }

        $response->setContent($this->render->postProcess($response));
    }

    /**
     * Add base snippets to the response.
     */
    protected function addSnippets()
    {
        $this->queue->add(Target::END_OF_HEAD, '<meta name="generator" content="Bolt">');

        if ($this->config->get('general/canonical')) {
            $canonical = $this->resources->getUrl('canonicalurl');
            $this->queue->add(Target::END_OF_HEAD, $this->encode('<link rel="canonical" href="%s">', $canonical));
        }

        if ($favicon = $this->config->get('general/favicon')) {
            $host = $this->resources->getUrl('hosturl');
            $theme = $this->resources->getUrl('theme');
            $this->queue->add(Target::END_OF_HEAD, $this->encode('<link rel="shortcut icon" href="%s%s%s">', $host, $theme, $favicon));
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
        $args = array_map(function ($str) {
            return htmlspecialchars($str, ENT_QUOTES);
        }, $args);
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
