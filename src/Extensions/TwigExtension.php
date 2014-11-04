<?php

namespace Bolt\Extensions;


class TwigExtension extends \Twig_Extension
{
    public $functions;
    public $filters;

    
    
    public function getFunctions()
    {
        return $this->functions;
    }
    
    public function getFilters()
    {
        return $this->filters;
    }
    
    
    /**
     * Add a Twig Function
     *
     * @param string $name
     * @param string $callback
     * @param array  $options
     */
    public function addTwigFunction(\Twig_SimpleFunction $twigFunction)
    {
        $this->functions[] = $twigFunction;
    }

    /**
     * Add a Twig Filter
     *
     * @param string $name
     * @param string $callback
     * @param array  $options
     */
    public function addTwigFilter(\Twig_SimpleFilter $twigFilter)
    {
        $this->filters[] = $twigFilter;
    }

    
}
