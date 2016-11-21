<?php

namespace Bolt\Storage\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 *  This class represents a single filter that converts to an expression along
 *  with associated query values.
 *
 *
 *  @author Ross Riley <riley.ross@gmail.com>
 */
class Filter
{
    protected $key;
    /** @var CompositeExpression */
    protected $expression;
    /** @var array */
    protected $parameters = [];

    /**
     * Sets the key that this filter affects.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Getter for key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the compiled expression as a string. This will look
     * something like `(alias.key = :placeholder)`.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression->__toString();
    }

    /**
     * Allows replacing the expression object with a modified one.
     *
     * @param CompositeExpression $expression
     */
    public function setExpression(CompositeExpression $expression)
    {
        $this->expression = $expression;
    }

    /**
     * Returns the actual object of the expression. This is generally
     * only needed for on the fly modification, to get the compiled
     * expression use getExpression().
     *
     * @return CompositeExpression
     */
    public function getExpressionObject()
    {
        return $this->expression;
    }

    /**
     * Returns the array of parameters attached to this filter. These are
     * normally used to replace placeholders at compile time.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Setter method to replace parameters.
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Helper method to check if parameters are set for a specific key.
     *
     * @param string $param
     *
     * @return bool
     */
    public function hasParameter($param)
    {
        return array_key_exists($param, $this->parameters);
    }

    /**
     * Allows setting a parameter for a single key.
     *
     * @param string $param
     * @param mixed  $value
     */
    public function setParameter($param, $value)
    {
        $this->parameters[$param] = $value;
    }
}
