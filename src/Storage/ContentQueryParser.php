<?php 
namespace Bolt\Storage;

use Bolt\Storage\EntityManager;


/**
* 
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
    
    protected $directives = [];
    
    protected $sqlParams = [];
    
    protected $operations = ['search', 'latest', 'first'];
    
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
    

}