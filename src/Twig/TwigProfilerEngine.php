<?php

namespace Bolt\Twig;

use Bolt\DataCollector\TwigDataCollector;
use Symfony\Bridge\Twig\TwigEngine;

class TwigProfilerEngine extends TwigEngine
{
    protected $environment;
    protected $twigEngine;
    protected $collector;

    public function __construct(\Twig_Environment $environment, TwigEngine $twigEngine, TwigDataCollector $collector)
    {
        $this->environment = $environment;
        $this->twigEngine  = $twigEngine;
        $this->collector   = $collector;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = array())
    {
        $templatePath = null;

        $loader = $this->environment->getLoader();
        if ($loader instanceof \Twig_Loader_Filesystem) {
            $templatePath = $loader->getCacheKey($name);
        }
        $this->collector->collectTemplateData($name, $parameters, $templatePath);

        return $this->twigEngine->render($name, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function stream($name, array $parameters = array())
    {
        $this->twigEngine->stream($name, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        return $this->twigEngine->exists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $this->twigEngine->supports($name);
    }
}
