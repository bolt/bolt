<?php 
namespace Bolt\Storage\Query;

use Bolt\Storage\EntityManager;


/**
*  Handler class to convert the DSL for content queries into an
*  object representation.
* 
*  @author Ross Riley <riley.ross@gmail.com>
*/
class ContentQueryParser
{
    
    protected $em;
    
    protected $query;
    
    protected $params;
    

    protected $contentTypes = [];
    
    protected $operation;
    
    protected $identifier;
    
    protected $selectType = 'all';
    
    protected $limit;
        
    protected $sqlParams = [];
    
    protected $operations = ['search', 'latest', 'first', 'random'];
    
    protected $getquery;
    
    protected $printquery;
    
    public function __construct(EntityManager $em, $query, array $params = [])
    {
        $this->em = $em;
        $this->query = $query;
        $this->params = $params;
    }
    
    public function parse()
    {
        $this->parseContent();
        $this->operation = $this->parseOperation();
        $this->parseDirectives();
    }
    
    /**
     * Parses the content area of the querystring.
     * 
     * @return void
     */
    protected function parseContent()
    {
        $contentString = strtok($this->query, '/');
        
        $content = array();
        $delim = "(),";
        $tok = strtok($contentString, $delim);
        while ($tok !== false) {
            $content[] = $tok;
            $tok = strtok($delim);
        }       
        
        $this->contentTypes = $content;
    }
    
    protected function parseOperation()
    {
        $operation = 'select';
        
        $queryParts = explode('/', $this->query);
        array_shift($queryParts);
        
        if (!count($queryParts)) {
            return $operation;
        }
        
        if (in_array($queryParts[0], $this->operations)) {
            $operation = array_shift($queryParts);
            $this->limit = array_shift($queryParts);
            $this->identifier = implode(',', $queryParts);
        } else {
            $this->identifier = implode(',', $queryParts);
        }
        
        return $operation;
        
    }
    
    protected function parseDirectives()
    {
        foreach ($this->params as $key => $value) {
            if ($key == 'printquery') {
                $this->printquery = true;
            }
            
            if ($key == 'getquery') {
                $this->getquery = $value;
            }
            
            if ($key == 'returnsingle') {
                $this->limit = 1;
            }
        }
    }
    
    public function getContentTypes()
    {
        return $this->contentTypes;
    }
    
    public function getOperation()
    {
        return $this->operation;
    }
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function getLimit()
    {
        return $this->limit;
    }
    
    /**
     * Adds a new operation to the list supported
     * @param string $operation name of operation to parse for
     */
    public function addOperation($operation)
    {
        if (!in_array($operation, $this->operations)) {
            $this->operations[] = $operation;
        }
    }
    
    /**
     * Removes an operation from the list supported
     * @param string $operation name of operation to remove
     */
    public function removeOperation($operation)
    {
        if (in_array($operation, $this->operations)) {
            $key = array_search($operation, $this->operations);
            unset($this->operations[$key]);
        }
    }
    

}