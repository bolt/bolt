<?php 

namespace Bolt\Storage\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
*  This class holds a set of filters that are made up of expressions and
*  values. It delegates parsing to the QueryParameterParser class
* 
* 
*  @author Ross Riley <riley.ross@gmail.com>
*/
class Filter
{
    
    protected $filters;
    protected $parser;
    
    protected $expression;
    protected $parameters = [];
    
    
    public function getExpression()
    {
        return $this->expression->__toString();
    }
    
    public function setExpression(CompositeExpression $expression)
    {
        $this->expression = $expression;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
    
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }
    
    public function hasParameter($param)
    {
        return array_key_exists($param, $this->parameters);
    }
    
    public function setParameter($param, $value)
    {
        $this->parameters[$param] = $value;
    }
    
    
    
}