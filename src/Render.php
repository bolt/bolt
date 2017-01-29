<?php

namespace Bolt;

use Bolt\Response\TemplateResponse;
use Silex;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper around Twig's render() function.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 * @deprecated Since 3.3, will be removed in 4.0.
 */
class Render
{
    public $app;
    /** @var boolean */
    public $safe;

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
    }

    /**
     * Render a template, possibly store it in cache. Or, if applicable, return the cached result.
     *
     * @param string|string[] $templateName Template name(s)
     * @param array           $context      Context variables
     * @param array           $globals      Global variables
     *
     * @return TemplateResponse
     *
     * @deprecated Since 3.3, will be removed in 4.0.
     */
    public function render($templateName, $context = [], $globals = [])
    {
        $template = $this->app['twig']->resolveTemplate($templateName);

        foreach ($globals as $name => $value) {
            $this->app['twig']->addGlobal($name, $value);
        }
        $globals = $this->app['twig']->getGlobals();

        $html = twig_include($this->app['twig'], $context, $template, [], true, false, $this->safe);

        $response = new TemplateResponse($template->getTemplateName(), $context, $globals);
        $response->setContent($html);

        return $response;
    }

    /**
     * @deprecated Since 3.3, will be removed in 4.0.
     *
     * Check if the template exists.
     *
     * @param string $template The name of the template.
     *
     * @return bool
     */
    public function hasTemplate($template)
    {
        $loader = $this->app['twig']->getLoader();

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
}
