<?php
namespace Bolt\Extensions;

class TwigProxy extends \Twig_Extension
{
    public $functions = array();
    public $filters = array();
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a Twig Function.
     *
     * @param \Twig_SimpleFunction $twigFunction
     */
    public function addTwigFunction(\Twig_SimpleFunction $twigFunction)
    {
        $this->functions[] = $twigFunction;
    }

    /**
     * Add a Twig Filter.
     *
     * @param \Twig_SimpleFilter $twigFilter
     */
    public function addTwigFilter(\Twig_SimpleFilter $twigFilter)
    {
        $this->filters[] = $twigFilter;
    }
}
