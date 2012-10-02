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

		if (!empty($values['username'])) {
			$this->user = $app['users']->getUser($values['username']);
		}

        // Check if the values need to be unserialized..
        foreach($this->values as $key => $value) {

            if (substr($value, 0, 2)=="a:") {
                $unserdata = @unserialize($value);
                if ($unserdata !== false) {
                    $this->values[$key] = $unserdata;
                }
            }
        }

    }

    public function setFromPost($values, $contenttype)
    {
        global $app;

        $values = cleanPostedData($values);

        // Some field type need to do things to the POST-ed value.
        foreach ($contenttype['fields'] as $fieldname => $field) {

            if ($field['type'] == "video" && isset($values[ $fieldname ])) {
                $video = $values[ $fieldname ];
                // update the HTML, according to given width and height
                if (!empty($video['width']) && !empty($video['height'])) {
                    $video['html'] = preg_replace("/width=(['\"])([0-9]+)(['\"])/i", 'width=${1}'.$video['width'].'${3}', $video['html']);
                    $video['html'] = preg_replace("/height=(['\"])([0-9]+)(['\"])/i", 'height=${1}'.$video['height'].'${3}', $video['html']);
                }

                $responsiveclass = "responsive-video";

                // See if it's widescreen or not..
                if (($video['width'] / $video['height']) > 1.76) {
                    $responsiveclass .= " widescreen";
                }

                if (strpos($video['url'], "vimeo") !== false) {
                    $responsiveclass .= " vimeo";
                }

                $video['responsive'] = sprintf('<div class="%s">%s</div>', $responsiveclass, $video['html']);

                $values[ $fieldname ] = $video;
            }

        }



        // TODO: check for allowed file types..

        // Handle file-uploads.
        if (!empty($_FILES)) {
            foreach($_FILES as $key => $file) {

                $filename = sprintf("%s/files/%s/%s",
                    $app['paths']['rootpath'], date("Y-m"), safeString($file['name'][0], false, "[]{}()"));
                $basename = sprintf("/%s/%s", date("Y-m"), safeString($file['name'][0], false, "[]{}()"));

                if ($file['error'][0] != UPLOAD_ERR_OK) {
                    $app['log']->add("Upload: Error occured during upload: " . $file['error'][0], 2);
                    continue;
                }

                if (substr($key, 0, 11)!="fileupload-") {
                    $app['log']->add("Upload: skipped an upload that wasn't for Content.", 2);
                    continue;
                }

                $fieldname = substr($key, 11);

                // Make sure the folder exists.
                makeDir(dirname($filename));

                // Check if we don't have doubles.
                if (is_file($filename)) {
                    while(is_file($filename)) {
                        $filename = $this->upcount_name($filename);
                        $basename = $this->upcount_name($basename);
                    }
                }

                if (is_writable(dirname($filename))) {
                    // Yes, we can create the file!
                    move_uploaded_file($file['tmp_name'][0], $filename);
                    $app['log']->add("Upload: uploaded file '$basename'.", 2);
                    $values[$fieldname] = $basename;
                } else {
                    $app['log']->add("Upload: couldn't write upload '$basename'.", 2);
                }

            }
        }

        $this->setValues($values);

    }

    // Taken from jQuery file upload..
    protected function upcount_name_callback($matches) {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return ' ('.$index.')'.$ext;
    }

    // Taken from jQuery file upload..
    protected function upcount_name($name) {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
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

        // For fields that are stored as arrays, like 'video'
        if (strpos($name, ".") > 0) {
            list ($name, $attr) = explode(".", $name);
            if (!empty($attr) && isset($this->values[$name][$attr])) {
                return $this->values[$name][$attr];
            }
        }

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


    /**
     * Gets the correct template to use, based on our cascading template rules.
     *
     */
    public function template()
    {
        global $app;

        $template = $app['config']['general']['record_template'];

        if (isset($this->contenttype['record_template'])) {
            $template = $this->contenttype['record_template'];
        }

        foreach($this->contenttype['fields'] as $name => $field) {
            if ($field['type']=="templateselect" && !empty($this->values[$name]) ) {
                $template = $this->values[$name];
            }
        }

        return $template;

    }

    /**
     * Get the fieldtype for a given fieldname.
     * @param $key
     * @return string
     */
    public function fieldtype($key)
    {

        foreach($this->contenttype['fields'] as $name => $field) {
            if ($name == $key) {
                return $field['type'];
            }
        }

        return "";

    }


    /**
     *
     * Create an excerpt for the content.
     *
     * @param int $length
     * @return string
     */
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