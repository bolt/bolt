<?php

namespace Bolt\Twig;

use Twig_SimpleFilter as SimpleFilter;
use Twig_SimpleFunction as SimpleFunction;

/**
 * Dynamic Twig Extension for Bolt Extensions.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DynamicExtension extends \Twig_Extension
{
    /** @var string */
    protected $name;
    /** @var SimpleFunction[] */
    protected $functions;
    /** @var SimpleFilter[] */
    protected $filters;

    /**
     * Constructor.
     *
     * @param string           $name
     * @param SimpleFunction[] $functions
     * @param SimpleFilter[]   $filters
     */
    public function __construct($name, array $functions = [], array $filters = [])
    {
        $this->name = $name;
        $this->functions = $functions;
        $this->filters = $filters;
    }

    /**
     * Add a function.
     *
     * @param SimpleFunction $function
     */
    public function addFunction(SimpleFunction $function)
    {
        $this->functions[] = $function;
    }

    /**
     * Add a filter.
     *
     * @param SimpleFilter $filter
     */
    public function addFilter(SimpleFilter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return $this->filters;
    }
}
