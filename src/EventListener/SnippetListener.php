<?php

namespace Bolt\EventListener;

use Bolt\Config;
use Bolt\Configuration\ResourceManager;
use Bolt\Controller\Zone;
use Bolt\Extensions;
use Bolt\Extensions\Snippets\Location;
use Bolt\Render;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SnippetListener implements EventSubscriberInterface
{
    /** @var Extensions */
    protected $extensions;
    /** @var Config */
    protected $config;
    /** @var ResourceManager */
    protected $resources;
    /** @var Render */
    protected $render;

    /**
     * SnippetListener constructor.
     *
     * @param Extensions      $extensions
     * @param Config          $config
     * @param ResourceManager $resources
     * @param Render          $render
     */
    public function __construct(Extensions $extensions, Config $config, ResourceManager $resources, Render $render)
    {
        $this->extensions = $extensions;
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
        if (!$this->isEnabled($event)) {
            return;
        }

        $response = $event->getResponse();
        if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
            return;
        }

        $this->addSnippets();

        $response->setContent($this->render->postProcess($response));
    }

    /**
     * Check if snippets are allowed for this request.
     *
     * @param FilterResponseEvent $event
     */
    protected function isEnabled(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return false;
        }
        if (Zone::isFrontend($event->getRequest())) {
            return true;
        }

        return $event->getRequest()->attributes->get('allow_snippets', false);
    }

    /**
     * Add base snippets to the response.
     */
    protected function addSnippets()
    {
        $this->insert('<meta name="generator" content="Bolt">');

        if ($this->config->get('general/canonical')) {
            $canonical = $this->resources->getUrl('canonicalurl');
            $this->insert($this->encode('<link rel="canonical" href="%s">', $canonical));
        }

        if ($favicon = $this->config->get('general/favicon')) {
            $host = $this->resources->getUrl('hosturl');
            $theme = $this->resources->getUrl('theme');
            $this->insert($this->encode('<link rel="shortcut icon" href="%s%s%s">', $host, $theme, $favicon));
        }
    }

    /**
     * Insert a snippet into the given location.
     *
     * @param string $snippet
     * @param string $location
     */
    protected function insert($snippet, $location = Location::END_OF_HEAD)
    {
        $this->extensions->insertSnippet($location, $snippet);
    }

    /**
     * Encode the snippet string and make it HTML safe.
     *
     * @param string $str
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
