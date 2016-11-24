<?php

namespace Bolt;

use Bolt\Response\TemplateResponse;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment as Environment;
use Twig_Template as Template;

/**
 * Wrapper around Twig's render() function.
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Render
{
    public $app;
    /** @var boolean */
    public $safe;
    /** @var string */
    public $twigKey;

    /**
     * Set up the object.
     *
     * @param \Silex\Application $app
     * @param bool               $safe
     */
    public function __construct(Silex\Application $app, $safe = false)
    {
        $this->app = $app;
        $this->safe = $safe;
        if ($safe) {
            $this->twigKey = 'safe_twig';
        } else {
            $this->twigKey = 'twig';
        }
    }

    /**
     * Render a template, possibly store it in cache. Or, if applicable, return the cached result.
     *
     * @param string $templateFile Template file name
     * @param array  $context      Context variables
     * @param array  $globals      Global variables
     *
     * @return TemplateResponse
     */
    public function render($templateFile, $context = [], $globals = [])
    {
        $this->app['stopwatch']->start('bolt.render', 'template');

        /** @var Environment $twig */
        $twig = $this->app[$this->twigKey];
        /** @var Template $template */
        $template = $twig->loadTemplate($templateFile);

        foreach ($globals as $name => $value) {
            $twig->addGlobal($name, $value);
        }

        $html = $template->render($context);

        $response = new TemplateResponse($html);
        $response
            ->setTemplate($template)
            ->setContext($context)
            ->setGlobals($globals)
        ;

        $this->app['stopwatch']->stop('bolt.render');

        return $response;
    }

    /**
     * Check if the template exists.
     *
     * @internal
     *
     * @param string $template The name of the template.
     *
     * @return bool
     */
    public function hasTemplate($template)
    {
        /** @var \Twig_Environment $env */
        $env = $this->app[$this->twigKey];
        $loader = $env->getLoader();

        /*
         * Twig_ExistsLoaderInterface is getting merged into
         * Twig_LoaderInterface in Twig 2.0. Check for this
         * instead once we are there, and remove getSource() check.
         */
        if ($loader instanceof \Twig_ExistsLoaderInterface) {
            return $loader->exists($template);
        }

        try {
            $loader->getSource($template);
        } catch (\Twig_Error_Loader $e) {
            return false;
        }

        return true;
    }

    /**
     * Post-process the rendered HTML: insert the snippets, and stuff.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function postProcess(Request $request, Response $response)
    {
        /** @var \Bolt\Asset\QueueInterface $queue */
        if (!$this->app['request_stack']->getCurrentRequest()->isXmlHttpRequest()) {
            foreach ($this->app['asset.queues'] as $queue) {
                $queue->process($request, $response);
            }
        }
    }
}
