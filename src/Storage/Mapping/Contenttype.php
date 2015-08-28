<?php 

namespace Bolt\Storage\Mapping;

class Contenttype extends \ArrayObject
{
    public function __construct($array){
        parent::__construct($array);
    }
    
    public function getFields()
    {
        return $this['fields'];
    }
    
    public function __toString()
    {
        return $this['slug'];
    }
}