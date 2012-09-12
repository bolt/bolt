<?php



class Content {

    public $id;
    public $values;
    public $taxonomy;
    public $contenttype;

    public function Content($values="", $contenttype="") 
    {
        
        if (!empty($values)) {
            $this->setValues($values);
        }
        
        if (!empty($contenttype)) {
            $this->setContenttype($contenttype);
        }        
    } 
    
    public function setValues($values) 
    {

        if (!empty($values['id'])) {
            $this->id = $values['id'];
        }
        
        $this->values = $values;
        
    }

    public function setContenttype($contenttype) 
    {
        
        $this->contenttype = $contenttype;
        
    }
    
    public function setTaxonomy($taxonomytype, $value) 
    {
        global $app;
        
        $this->taxonomy[$taxonomytype][] = $value;
        
        // If it's a "grouping" type, set $this->group.
        if ($app['config']['taxonomy'][$taxonomytype]['behaves_like'] == "grouping") {
            $this->setGroup($value);
        }
        
        
    }
    
    
    public function getTaxonomyType($type) {
    
        echo "<pre>" . util::var_dump($this->config['taxonomy'], true) . "</pre>";
        
    
        if (isset($this->config['taxonomy'][$type])) {
            return $this->config['taxonomy'][$type];
        } else {
            return false;
        }
    
    }    
    
    public function setGroup($value) 
    {
        $this->group = $value;
    }    
 
    /**
     * magic __call function, used for when templates use {{ content.title }}, 
     * so we can map it to $this->values['title']
     */
    public function __call($name, $arguments)
    {
        if (isset($this->values[$name])) {

            // This is too invasive. for now, only add editable when needed
            /*
            $fieldtype = $this->contenttype['fields'][$name]['type'];
            
            if (in_array($fieldtype, array('html', 'text', 'textarea'))) {
                $output = sprintf("<div class='pilex-editable'>%s</div>", $this->values[$name]);
            } else {
                $output = $this->values[$name];
            }
            
            return $output;
            */
            
            return $this->values[$name];

        } else {
            return false;
        }
    }   
    
    /**
     * magic __call function, used for when templates use {{ content.title }}, 
     * so we can map it to $this->values['title']
     */
    public function get($name)
    {
        if (isset($this->values[$name])) {   
            return $this->values[$name];

        } else {
            return false;
        }
    }    
         
    
    /**
     * Creates a link to the content record
     */
    public function link($param="") 
    {
        
        // TODO: use Silex' UrlGeneratorServiceProvider instead.
        $link = sprintf("/%s/%s", $this->contenttype['singular_slug'], $this->values['slug'] );
        
        return $link;
        
    }
    
    
    public function excerpt($length=200) {

        $excerpt = array();

        foreach ($this->contenttype['fields'] as $key => $field) {           
            if (in_array($field['type'], array('text', 'html', 'textarea')) 
                && isset($this->values[$key])
                && !in_array($key, array("title", "name")) ) {
                $excerpt[] = $this->values[$key];
            }
        }

        $excerpt = implode(" x ", $excerpt);

        $excerpt = trimText(strip_tags($excerpt), $length) ;

        return $excerpt;
        
    }
    
    
}