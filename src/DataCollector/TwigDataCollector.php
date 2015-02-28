<?php

namespace Bolt\DataCollector;

use Bolt\Application;
use Bolt\Library as Lib;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * TwigDataCollector.
 *
 * @author Vincent Bouzeran <vincent.bouzeran@elao.com>
 */
class TwigDataCollector extends DataCollector
{
    private $app;

    protected $data;

    private $trackedvalues;

    /**
     * The Constructor for the Twig Datacollector.
     *
     * @param Application $app The Silex app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Collect information from Twig.
     *
     * @param Request    $request   The Request Object
     * @param Response   $response  The Response Object
     * @param \Exception $exception The Exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $filters = array();
        $tests = array();
        $extensions = array();
        $functions = array();

        foreach ($this->getTwig()->getExtensions() as $extensionName => $extension) {
            /** @var $extension \Twig_ExtensionInterface */
            $extensions[] = array(
                'name'  => $extensionName,
                'class' => get_class($extension)
            );
            foreach ($extension->getFilters() as $filterName => $filter) {
                if ($filter instanceof \Twig_FilterInterface) {
                    $call = $filter->compile();
                    if (is_array($call) && is_callable($call)) {
                        $call = 'Method ' . $call[1] . ' of an object ' . get_class($call[0]);
                    }
                } elseif ($filter instanceof \Twig_SimpleFilter) {
                    $call = $filter->getName();
                } else {
                    continue;
                }

                $filters[] = array(
                    'name'      => $filterName,
                    'extension' => $extensionName,
                    'call'      => $call,
                );
            }

            foreach ($extension->getTests() as $testName => $test) {
                if ($test instanceof \Twig_TestInterface) {
                    $call = $test->compile();
                } elseif ($test instanceof \Twig_SimpleTest) {
                    $call = $test->getName();
                } else {
                    continue;
                }

                $tests[] = array(
                    'name'      => $testName,
                    'extension' => $extensionName,
                    'call'      => $call,
                );
            }

            foreach ($extension->getFunctions() as $functionName => $function) {
                if ($function instanceof \Twig_FunctionInterface) {
                    $call = $function->compile();
                } elseif ($function instanceof \Twig_SimpleFunction) {
                    $call = $function->getName();
                } else {
                    continue;
                }

                $functions[] = array(
                    'name'      => $functionName,
                    'extension' => $extensionName,
                    'call'      => $call,
                );
            }
        }

        $this->data = array(
            'extensions'     => $extensions,
            'tests'          => $tests,
            'filters'        => $filters,
            'functions'      => $functions,
            'templates'      => Lib::parseTwigTemplates($this->app['twig.loader']),
            'templatechosen' => $this->getTrackedValue('templatechosen'),
            'templateerror'  => $this->getTrackedValue('templateerror')
        );
    }

    /**
     * Get Twig Environment.
     *
     * @return \Twig_Environment
     */
    protected function getTwig()
    {
        return $this->app['twig'];
    }

    /**
     * Collects data on the twig templates rendered.
     *
     * @param mixed $templateName The template name
     * @param array $parameters   The array of parameters passed to the template
     * @param array $templatePath The template path
     */
    public function collectTemplateData($templateName, $parameters = null, $templatePath = null)
    {
        $collectedParameters = array();
        if (is_array($parameters)) {
            foreach ($parameters as $name => $value) {
                $collectedParameters[$name] = array(
                    'type'  => in_array(gettype($value), array('object', 'resource')) ? get_class($value) : gettype($value),
                    'value' => in_array(gettype($value), array('object', 'resource', 'array')) ? null : $value,
                );
            }
        }
        $this->data['templates'][] = array(
            'name'       => $templateName,
            'path'       => $templatePath,
            'parameters' => $collectedParameters
        );
    }

    /**
     * Returns the amount of Templates.
     *
     * @return int Amount of templates
     */
    public function getCountTemplates()
    {
        return count($this->getTemplates());
    }

    /**
     * Returns the Twig templates information.
     *
     * @return array Template information
     */
    public function getTemplates()
    {
        return isset($this->data['templates']) ? $this->data['templates'] : array();
    }

    /**
     * Getter for templatechosen.
     *
     * @return string
     */
    public function getChosenTemplate()
    {
        return $this->data['templatechosen'];
    }

    /**
     * Getter for templateerror.
     *
     * @return string
     */
    public function getTemplateError()
    {
        return $this->data['templateerror'];
    }

    /**
     * Returns the amount of Extensions.
     *
     * @return integer Amount of Extensions
     */
    public function getCountExtensions()
    {
        return count($this->getExtensions());
    }

    /**
     * Returns the Twig Extensions Information.
     *
     * @return array Extension Information
     */
    public function getExtensions()
    {
        return $this->data['extensions'];
    }

    /**
     * Returns the amount of Filters.
     *
     * @return integer Amount of Filters
     */
    public function getCountFilters()
    {
        return count($this->getFilters());
    }

    /**
     * Returns the Filter Information.
     *
     * @return array Filter Information
     */
    public function getFilters()
    {
        return $this->data['filters'];
    }

    /**
     * Returns the amount of Twig Tests.
     *
     * @return integer Amount of Tests
     */
    public function getCountTests()
    {
        return count($this->getTests());
    }

    /**
     * Returns the Tests Information.
     *
     * @return array Tests Information
     */
    public function getTests()
    {
        return $this->data['tests'];
    }

    /**
     * Returns the amount of Twig Functions.
     *
     * @return integer Amount of Functions
     */
    public function getCountFunctions()
    {
        return count($this->getFunctions());
    }

    /**
     * Returns Twig Functions Information.
     *
     * @return array Function Information
     */
    public function getFunctions()
    {
        return $this->data['functions'];
    }

    public function getDisplayInWdt()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'twig';
    }

    /**
     * Setting a value for later use.
     *
     * @param string $key
     * @param string $value
     */
    public function setTrackedValue($key, $value)
    {
        $this->trackedvalues[$key] = $value;
    }

    /**
     * Getting a previously set value.
     *
     * @param string $key
     *
     * @return string
     */
    public function getTrackedValue($key)
    {
        if (isset($this->trackedvalues[$key])) {
            return $this->trackedvalues[$key];
        } else {
            return false;
        }
    }
}
