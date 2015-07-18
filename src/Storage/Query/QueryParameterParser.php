<?php 
namespace Bolt\Storage\Query;

use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\Expr\Comparison;


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
    
    protected $valueMatchers = [];
    protected $keyMatchers = [];
    
    
    public function __construct($key, $value = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->setupDefaults();
    }
    
    public function setupDefaults()
    {
        $this->addValueMatcher('<(\w+)' , ['value'=>"$1", 'operator' => Comparison::LT]);
        $this->addValueMatcher('<=(\w+)', ['value'=>"$1", 'operator' => Comparison::LTE]);
        $this->addValueMatcher('>=(\w+)', ['value'=>"$1", 'operator' => Comparison::GTE]);
        $this->addValueMatcher('>(\w+)' , ['value'=>"$1", 'operator' => Comparison::GT]);
        $this->addValueMatcher('!(\w+)',  ['value'=>"$1", 'operator' => Comparison::NEQ]);
        $this->addValueMatcher('(%\w+|\w+%|%\w+%)',  ['value'=>"$1", 'operator' => 'LIKE']);
        $this->addValueMatcher(
            '(\w+) ?\|\| ?(\w+)',  
            [
                'value'=>'$1,$2', 
                'operator' => 'orX'
            ]);
        $this->addValueMatcher('(\w+)',   ['value'=>"$1", 'operator' => Comparison::EQ]);
    }
    
    /**
     * Runs the keys/values through the relevant parsers
     * 
     * @return array matched values
     */
    public function parse()
    {
        foreach ($this->valueMatchers as $matcher) {
            $regex = sprintf('/%s/', $matcher['token']);
            $values = $matcher['params'];
            $values['key'] = $this->key;
            if (preg_match($regex, $this->value)) {
                $values['value'] = preg_replace($regex, $values['value'], $this->value);
                $values['matched'] = $matcher['token'];
                return $values;
            }
        }
        
    }
    
    public function getExpression()
    {
        
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