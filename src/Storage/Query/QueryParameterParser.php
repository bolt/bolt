<?php 
namespace Bolt\Storage\Query;

use Bolt\Exception\QueryParseException;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Query\Filter;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;


/**
*  Handler class to convert the DSL for content query parameters
*  into equivalent DBAL expressions.
* 
*  @author Ross Riley <riley.ross@gmail.com>
*/
class QueryParameterParser
{
    
    protected $key;
    protected $value;
    protected $expr;
    
    protected $valueMatchers = [];
    protected $keyMatchers = [];
    
    
    public function __construct($key, $value = null, QueryBuilder $qb = null)
    {
        $this->key = $key;
        $this->value = $value;
        
        if ($qb) {
            $this->expr = $qb->expr();   
        }
        $this->setupDefaults();
    }
    
    public function setupDefaults()
    {
        $this->addValueMatcher('<(\w+)' , ['value'=>"$1", 'operator' => 'lt']);
        $this->addValueMatcher('<=(\w+)', ['value'=>"$1", 'operator' => 'lte']);
        $this->addValueMatcher('>=(\w+)', ['value'=>"$1", 'operator' => 'gte']);
        $this->addValueMatcher('>(\w+)' , ['value'=>"$1", 'operator' => 'gt']);
        $this->addValueMatcher('!$',      ['value'=>"",   'operator' => 'isNotNull']);
        $this->addValueMatcher('!(\w+)',  ['value'=>"$1", 'operator' => 'neq']);
        $this->addValueMatcher('!\[([\w ,]+)\]',  ['value'=>"$1", 'operator' => 'notIn']);
        $this->addValueMatcher('\[([\w ,]+)\]',  ['value'=>"$1", 'operator' => 'in']);
        $this->addValueMatcher('(%\w+|\w+%|%\w+%)',  ['value'=>"$1", 'operator' => 'like']);
        $this->addValueMatcher('(\w+)',   ['value'=>"$1", 'operator' => 'eq']);
    }
    
    /**
     * Runs the keys/values through the relevant parsers
     * 
     * @return Expression matched values
     * @throws Bolt\Exception\QueryParseException
     */
    public function getFilter()
    {
        $filter = new Filter();
        $filterParams = [];
        
        // This first block makes sure that invalid queries are caught
        if (!$this->expr instanceof ExpressionBuilder) {
            throw new QueryParseException("Cannot call method without an Expression Builder parameter set", 1);
        }
        
        if (strpos($this->value, '&&') && strpos($this->value, '||')) {
            throw new QueryParseException("Mixed && and || operators are not supported", 1);
        }
        
        // This block handles triple pipe queries
        if (strpos($this->key, '|||')){
            $keys = preg_split('/ *(\|\|\|) */', $this->key);
            $values = preg_split('/ *(\|\|\|) */', $this->value);
            $values = array_pad($values, count($keys), end($values));
            $parts = [];
            $count = 1;
            while (($key = array_shift($keys)) && ($val = array_shift($values))) {
                $val = $this->parseValue($val);
                $placeholder = $key."_".$count;
                $filterParams[$placeholder] = $val['value'];
                $exprMethod = $val['operator'];
                $parts[] = $this->expr->$exprMethod($key, ":$placeholder");
                $count++;
            }
            
            $filter->setExpression(call_user_func_array([$this->expr, 'orX'], $parts));
            $filter->setParameters($filterParams);
            return $filter;
        }
        
        // This block handles the parse if the query is a composite and / or filter
        if (strpos($this->value, '&&') || strpos($this->value, '||')) {
            $values = preg_split('/ *(&&|\|\|) */', $this->value, -1, PREG_SPLIT_DELIM_CAPTURE);
            $op = $values[1];
            $key = $this->key;
            
            if ($op === '&&') {
                $comparison = 'andX';
            } elseif($op === '||') {
                $comparison = 'orX';
            }
            

            $values = array_diff($values, ['&&', '||']);
            $parts = [];
            $count = 1;
            while ($val = array_shift($values)) {
                $val = $this->parseValue($val);
                $placeholder = $key."_".$count;
                $filterParams[$placeholder] = $val['value'];
                $exprMethod = $val['operator'];
                $parts[] = $this->expr->$exprMethod($key, ":$placeholder");
                $count++;
            }
            
            $filter->setExpression(call_user_func_array([$this->expr, $comparison], $parts));
            $filter->setParameters($filterParams);
            return $filter;          
        }
        
        // Finally this block handle the simple key to value queries     
        $val = $this->parseValue($this->value);
        $key = $this->key;
        $placeholder = $key."_1";
        $exprMethod = $val['operator'];
        $filter->setExpression($this->expr->andX($this->expr->$exprMethod($key, ":$placeholder")));
        $filter->setParameters([$val['value']]);
        
        return $filter;
    }
        
    public function parseValue($value = null)
    {
        if (!$value) {
            $value = $this->value;
        }
        foreach ($this->valueMatchers as $matcher) {
            $regex = sprintf('/%s/', $matcher['token']);
            $values = $matcher['params'];
            if (preg_match($regex, $value)) {
                $values['value'] = preg_replace($regex, $values['value'], $value);
                $values['matched'] = $matcher['token'];
                return $values;
            }
        }
    }
    
    
    /**
     * Adds an additional token to parse for value parameters
     * 
     * @param string $token  regex pattern to match against
     * @param array  $params array of options to provide to the matched param
     */
    public function addValueMatcher($token, $params = [])
    {
        $this->valueMatchers[] = ['token'=>$token, 'params'=>$params];
    }
    
    
    
    
}