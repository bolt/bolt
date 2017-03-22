<?php

namespace Bolt\Twig;

use Twig_Markup as Markup;
use Twig_Sandbox_SecurityError as SecurityError;
use Twig_Sandbox_SecurityNotAllowedFilterError as SecurityNotAllowedFilterError;
use Twig_Sandbox_SecurityNotAllowedFunctionError as SecurityNotAllowedFunctionError;
use Twig_Sandbox_SecurityNotAllowedTagError as SecurityNotAllowedTagError;
use Twig_Sandbox_SecurityPolicyInterface as SecurityPolicyInterface;
use Twig_TemplateInterface as TemplateInterface;

/**
 * Security policy enforced in sandbox mode.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityPolicy implements SecurityPolicyInterface
{
    /** @var array */
    private $allowedTags;
    /** @var array */
    private $allowedFilters;
    /** @var array */
    private $allowedMethods;
    /** @var array */
    private $allowedProperties;
    /** @var array */
    private $allowedFunctions;

    /**
     * Constructor.
     *
     * @param array $allowedTags
     * @param array $allowedFilters
     * @param array $allowedMethods
     * @param array $allowedProperties
     * @param array $allowedFunctions
     */
    public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedMethods = [], array $allowedProperties = [], array $allowedFunctions = [])
    {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->setAllowedMethods($allowedMethods);
        $this->allowedProperties = $allowedProperties;
        $this->allowedFunctions = $allowedFunctions;
    }

    /**
     * Add tag allowed by this policy.
     *
     * @param string $tag
     */
    public function addAllowedTag($tag)
    {
        $this->allowedTags[] = $tag;
    }

    /**
     * @param array $tags
     */
    public function setAllowedTags(array $tags)
    {
        $this->allowedTags = $tags;
    }

    /**
     * Add filter allowed by this policy.
     *
     * @param string $filter
     */
    public function addAllowedFilter($filter)
    {
        $this->allowedFilters[] = $filter;
    }

    /**
     * @param array $filters
     */
    public function setAllowedFilters(array $filters)
    {
        $this->allowedFilters = $filters;
    }

    /**
     * Add function allowed by this policy.
     *
     * @param string $function
     */
    public function addAllowedFunction($function)
    {
        $this->allowedFunctions[] = $function;
    }

    /**
     * @param array $functions
     */
    public function setAllowedFunctions(array $functions)
    {
        $this->allowedFunctions = $functions;
    }

    /**
     * Add class method allowed by this policy.
     *
     * @param string $class
     * @param string $method
     */
    public function addAllowedMethod($class, $method)
    {
        if (!isset($this->allowedMethods[$class])) {
            $this->allowedMethods[$class] = [];
        }
        $this->allowedMethods[$class][] = strtolower($method);
    }

    /**
     * @param array $methods
     */
    public function setAllowedMethods(array $methods)
    {
        $this->allowedMethods = [];
        foreach ($methods as $class => $m) {
            $this->allowedMethods[$class] = array_map('strtolower', is_array($m) ? $m : [$m]);
        }
    }

    /**
     * Add class property allowed by this policy.
     *
     * @param string $class
     * @param string $property
     */
    public function addAllowedProperty($class, $property)
    {
        if (!isset($this->allowedProperties[$class])) {
            $this->allowedProperties[$class] = [];
        }
        $this->allowedProperties[$class][] = $property;
    }

    /**
     * @param array $properties
     */
    public function setAllowedProperties(array $properties)
    {
        $this->allowedProperties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function checkSecurity($tags, $filters, $functions)
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->allowedTags)) {
                throw new SecurityNotAllowedTagError(sprintf("Tag '%s' is not allowed.", $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf("Filter '%s' is not allowed.", $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (!in_array($function, $this->allowedFunctions)) {
                throw new SecurityNotAllowedFunctionError(sprintf("Function '%s' is not allowed.", $function), $function);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkMethodAllowed($obj, $method)
    {
        if ($obj instanceof TemplateInterface || $obj instanceof Markup) {
            return;
        }

        if ($obj instanceof SecurityProxyInterface) {
            $objClass = $obj->getProxiedClass();
        } else {
            $objClass = get_class($obj);
        }

        $allowed = false;
        $method = strtolower($method);

        foreach ($this->allowedMethods as $class => $methods) {
            if (!$this->matchAnyClassInTree($class, $objClass)) {
                continue;
            }
            if ($this->globMatchAll($methods, $method)) {
                $allowed = true;

                break;
            }
        }

        if (!$allowed) {
            throw new SecurityError(sprintf("Calling '%s' method on a '%s' object is not allowed.", $method, $objClass));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkPropertyAllowed($obj, $property)
    {
        $allowed = false;

        if ($obj instanceof SecurityProxyInterface) {
            $objClass = $obj->getProxiedClass();
        } else {
            $objClass = get_class($obj);
        }

        foreach ($this->allowedProperties as $class => $properties) {
            if (!$this->matchAnyClassInTree($class, $objClass)) {
                continue;
            }
            if ($this->globMatchAll((array) $properties, $property)) {
                $allowed = true;

                break;
            }
        }

        if (!$allowed) {
            throw new SecurityError(sprintf("Calling '%s' property on a '%s' object is not allowed.", $property, $objClass));
        }
    }

    protected function matchAnyClassInTree($class, $objClass)
    {
        foreach ($this->getAllClasses($objClass) as $aClass) {
            if ($this->globMatch($class, $aClass)) {
                return true;
            }
        }

        return false;
    }

    protected function getAllClasses($class)
    {
        return array_reverse([$class => $class] + class_parents($class) + class_implements($class));
    }

    protected function globMatchAll($patterns, $string, $ignoreCase = true)
    {
        foreach ($patterns as $pattern) {
            if ($this->globMatch($pattern, $string, $ignoreCase)) {
                return true;
            }
        }

        return false;
    }

    protected function globMatch($pattern, $string, $ignoreCase = true)
    {
        $expr = preg_replace_callback('/[\\\\^$.[\\]|()?*+{}\\-\\/]/', function ($matches) {
            switch ($matches[0]) {
                case '*':
                    return '.*';
                case '?':
                    return '.';
                default:
                    return '\\' . $matches[0];
            }
        }, $pattern);

        $expr = '/' . $expr . '/';
        if ($ignoreCase) {
            $expr .= 'i';
        }

        return (bool) preg_match($expr, $string);
    }
}
