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
        } else {
            // Return an '(undefined contenttype)'..
            if (is_array($contenttype)) {
                $contenttype = $contenttype['name'];
            }
            $values = array(
                'name' => "(undefined $contenttype)",
                'title' => "(undefined $contenttype)"
            );
            $this->setValues($values);
        }
        
        if (!empty($contenttype)) {
            $this->setContenttype($contenttype);
        }        
    } 
    
    public function setValues($values) 
    {
		global $app;

        if (!empty($values['id'])) {
            $this->id = $values['id'];
        }
        
        $this->values = $values;

        if (!isset($this->values['datecreated']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datecreated'])) {
            $this->values['datecreated'] = "1970-01-01 00:00:00";
        }

        if (!isset($this->values['datechanged']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = "1970-01-01 00:00:00";
        }

		echo "user: " . $values['username'];
		
		if (!empty($values['username']) {
			$this->user = $app['users']->getUsers($values['username']);
		}

    }

    public function setFromPost($values) {

        $values = cleanPostedData($values);

        $this->setValues($values);

    }


    public function setContenttype($contenttype) 
    {
        global $app;

        if (is_string($contenttype)) {
            $contenttype = $app['storage']->getContenttype($contenttype);
        }

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
                $output = sprintf("<div class='bolt-editable'>%s</div>", $this->values[$name]);
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
     * pseudo-magic function, used for when templates use {{ content.get(title) }},
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
     * Get the title, name, caption or subject..
     */
    public function title()
    {

        if (isset($this->values['title'])) {
            return $this->values['title'];
        } else if (isset($this->values['name'])) {
            return $this->values['name'];
        } else if (isset($this->values['caption'])) {
            return $this->values['caption'];
        } else if (isset($this->values['subject'])) {
            return $this->values['subject'];
        } else {
            return "(untitled)";
        }


    }




    /**
     * Creates a link to the content record
     */
    public function link($param="") 
    {
        global $app;

        // TODO: use Silex' UrlGeneratorServiceProvider instead.

        // If there's no valid content, return no link.
        if (empty($this->id)) {
            return "";
        }

        $link = sprintf("%s%s/%s",
            $app['paths']['root'],
            $this->contenttype['singular_slug'],
            $this->values['slug'] );
        
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

        $excerpt = implode(" ", $excerpt);

        $excerpt = trimText(strip_tags($excerpt), $length) ;

        return $excerpt;
        
    }
    
    
}