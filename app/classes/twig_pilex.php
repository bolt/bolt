<?php

/**
 * The class for Pilex' Twig tags, functions and filters.
 */
class Pilex_Twig_Extension extends Twig_Extension
{
    public function getName()
    {
        return 'pilex';
    }
    
    public function getFunctions()
    {
        return array(
            'print' => new Twig_Function_Method($this, 'twig_print', array('is_safe' => array('html'))),
            'excerpt' => new Twig_Function_Method($this, 'twig_excerpt'),
            'trimtext' => new Twig_Function_Method($this, 'twig_trim'),
            'link' => new Twig_Function_Method($this, 'twig_link'),
            'current' => new Twig_Function_Method($this, 'twig_current'),
            'token' => new Twig_Function_Method($this, 'twig_token'),
            'listtemplates' => new Twig_Function_Method($this, 'twig_listtemplates'),
            'pager' => new Twig_Function_Method($this, 'twig_pager', array('needs_environment' => true)),
            'max' => new Twig_Function_Method($this, 'twig_max'),
            'min' => new Twig_Function_Method($this, 'twig_min'),
            'request' => new Twig_Function_Method($this, 'twig_request'),
            'content' => new Twig_Function_Method($this, 'twig_content'),
            'ismobileclient' => new Twig_Function_Method($this, 'twig_ismobileclient'),
            'menu' => new Twig_Function_Method($this, 'menu', array('needs_environment' => true)),           
        );
    }    
  
  
    public function getFilters()
    {
        return array(
            'rot13' => new Twig_Filter_Method($this, 'rot13Filter'),
            'trimtext' => new Twig_Filter_Method($this, 'twig_trim'),
            'ucfirst' => new Twig_Filter_Method($this, 'twig_ucfirst'),
            'excerpt' => new Twig_Filter_Method($this, 'twig_excerpt'),
            'link' => new Twig_Filter_Method($this, 'twig_link'),
            'current' => new Twig_Filter_Method($this, 'twig_current'),
            'trans' => new Twig_Filter_Method($this, 'twig_trans'),
            'transchoice' => new Twig_Filter_Method($this, 'twig_trans'),
            'thumbnail' => new Twig_Filter_Method($this, 'twig_thumbnail'),
            'shadowbox' => new Twig_Filter_Method($this, 'twig_shadowbox', array('is_safe' => array('html'))),

        );
    }


    /**
     * Output pretty-printed arrays.
     *
     * @see util::php
     * @see http://brandonwamboldt.github.com/utilphp/
     *
     * @param mixed $var
     * return string
     */
    public function twig_print($var) 
    {
        
        $output = util::var_dump($var, true);
        
        return $output;
        
    }
    
    
    /**
     * Create an excerpt for the given content
     */
    public function twig_excerpt($content, $length=200)
    {
    
        
        if (isset($content['contenttype']['fields'])) {
            // best option: we've got the contenttype fields, so we can use that to 
            // determine the excerpt. 
    
            unset($content['name'], $content['title']); 
            
            $output = array();
            
            foreach ($content['contenttype']['fields'] as $key => $field) {           
                if (in_array($field['type'], array('text', 'html', 'textarea')) && isset($content[$key])) {
                    $output[] = $content[$key];
                }
            }
            
            $output = implode(" ", $output);
            
        } else if (is_array($content)) {
            // Assume it's an array, strip some common fields that we don't need, implode the rest..
            
            unset($content['id'], $content['slug'], $content['datecreated'], $content['datechanged'], 
                $content['username'], $content['title'], $content['contenttype'], $content['status'], $content['taxonomy']
                );  
            $output = implode(" ", $content);
            
        } else if (is_string($content)) {
            // otherwise we just use the string..
            
            $output = $content;
            
        } else {
            // Nope, got nothing.. 
            
            $output = "";
            
        }
    
        $output = trimText(strip_tags($output), $length) ;
    
    
        return $output;
        
    }
    
    /**
     * Trimtexts the given string.
     */
    public function twig_trim($content, $length=200) 
    {
    
        $output = trimText(strip_tags($content), $length) ;
        
        return $output;
        
    }
    
    
    /**
     * UCfirsts the given string.
     */
    public function twig_ucfirst($str, $param="") 
    {
        
        return ucfirst($str);
        
    }
    
    /**
     * Creates a link to the given content record
     */
    public function twig_link($content, $param="") 
    {
        
        // TODO: use Silex' UrlGeneratorServiceProvider instead.
        $link = sprintf("/%s/%s", $content['contenttype']['singular_slug'], $content['slug']);
        
        return $link;
        
    }

    
    /** 
     * Returns true, if the given content is the current content.
     *
     * If we're on page/foo, and content is that page, you can use 
     * {% is page|current %}class='active'{% endif %}
     */
    public function twig_current($content, $param="") 
    {
        global $app;
        
        $route_params = $app['request']->get('_route_params');  
        
        // No contenttypeslug or slug -> not 'current'
        if (empty($route_params['contenttypeslug']) || empty($route_params['slug'])) {
            return false;
        }
        
        // if the current requested page is for the same slug or singularslug..
        if ($route_params['contenttypeslug'] == $content['contenttype']['slug'] ||
            $route_params['contenttypeslug'] == $content['contenttype']['singular_slug']) {
            
            // .. and the slugs should match..
            if ($route_params['slug'] == $content['slug']) {
                return true;    
            }
        }
        
        return false;
         
    }
    
    
    
    /**
     * get a simple CSRF-like token
     *
     * @see getToken()
     * @return string 
     */
    public function twig_token() 
    {
            
        return getToken();
        
    }
    
    
    /**
     * lists templates, optionallyfiltered by $filter
     *
     * @param string $filter
     * @return string 
     */
    public function twig_listtemplates($filter="") 
    {
            
        $files = array();
    
        $foldername = realpath(__DIR__.'/../view');
    
        $d = dir($foldername);
        
        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");
        
        while (false !== ($file = $d->read())) {
            
            if (in_array($file, $ignored)) { continue; }
            
            if (is_file($foldername."/".$file) && is_readable($foldername."/".$file)) {        
                
                if (!empty($filter) && !fnmatch($filter, $file)) {
                    continue;   
                }
                
                // Skip filenames that start with _ 
                if ($file[0] == "_") {
                    continue;
                }
                          
                $files['view/'.$file] = $file;       
            }
            
            
        }
            
        $d->close();    
    
        return $files;
        
    }
    
    
    /**
     * output a simple pager, for paged pages.
     *
     * @param array $pager
     * @return string HTML
     */
    public function twig_pager(Twig_Environment $env, $surr=4, $class="", $pager='') 
    {
        global $app;
            
        if (empty($pager)) {
            $pager = $app['pager'];
        }
         
        echo $env->render('_sub_pager.twig', array('pager' => $pager, 'surr' => $surr, 'class' => $class));
            
    }
    
    
    /**
     * Get 'content' 
     *
     * usage:
     * To get 10 entries, in order: {% set entries = content('entries', {'limit': 10}) %}
     * 
     * To get the latest single page: {% set page = content('page', {'order':'datecreated desc'}) %}
     *
     * To get 5 upcoming events: {% set events = content('events', {'order': 'date asc', 'where' => 'date < now()' }) %}
     */
    public function twig_content($contenttypeslug, $params) 
    {
        global $app; /* TODO: figure out if there's a way to do this without globals.. */
    
        $contenttype = $app['storage']->getContentType($contenttypeslug);
            
        // If the contenttype doesn't exist, return an empty array
        if (!$contenttype) {
            $app['monolog']->addWarning("contenttype '$contenttypeslug' doesn't exist.");
            return array();
        }
        
        if ($app['request']->query->get('page') != "") {
            $params['page'] = $app['request']->query->get('page');
        }
        
        if ((makeSlug($contenttypeslug) == $contenttype['singular_slug']) || $params['limit']==1) {
            // If we used the singular version of the contenttype, or we specifically request only one result.. 
            $content = $app['storage']->getSingleContent($contenttypeslug, $params);
        } else {
            // Else, we get more than one result
            $content = $app['storage']->getContent($contenttypeslug, $params, $pager);
            $app['pager'] = $pager;
        }
        
        
        return $content; 
        
    }
    
    
        
    
    /**
     * return the 'max' of two values..
     *
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     */
    public function twig_max($a, $b) 
    {
        return max($a, $b);        
    }
    
    
    /**
     * return the 'min' of two values..
     *
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     */
    public function twig_min($a, $b) 
    {
        return min($a, $b);        
    }
    

    
    /**
     * return the requested parameter from $_REQUEST, $_GET or $_POST..
     *
     * @param string $parameter
     * @param string $first
     * @return mixed
     */
    public function twig_request($parameter, $first="") 
    {
    
        if ($first=="get" && isset($_GET[$parameter])) {
            return $_GET[$parameter];
        } else if ($first=="post" && isset($_POST[$parameter])) {
            return $_POST[$parameter];
        } else if (isset($_REQUEST[$parameter])) {
            return $_REQUEST[$parameter];
        } else {
            return false;
        } 
        
    }
    

    
    /**
     * Stub for the 'trans' and 'transchoice' filters.
     */
    public function twig_trans($str) 
    {
            return $str;
    }
    
    
    /**
     * Helper function to make a path to an image thumbnail.
     *
     */
    public function twig_thumbnail($filename, $width=100, $height=100, $crop="") 
    {
        
        $thumbnail = sprintf("%s/%sx%s%s/%s", 
            "/thumbs",
            $width, 
            $height,
            $crop,
            $filename
            );
        
        return $thumbnail;
        
    }
    
    
    
    /**
     * Helper function to wrap an image in a shadowbox HTML tag, with thumbnail
     *
     * example: {{ content.image|shadowbox(320, 240) }}
     */
    public function twig_shadowbox($filename="", $width=100, $height=100, $crop="") 
    {
        
        if (!empty($filename)) {
    
            $thumbnail = $this->twig_thumbnail($filename, $width, $height, $crop);
            $large = $this->twig_thumbnail($filename, 1000, 1000, 'r');
        
            $shadowbox = sprintf('<a href="%s" rel="shadowbox" title="Image: %s">
                    <img src="%s" width="%s" height="%s"></a>', 
                    $large, $filename, $thumbnail, $width, $height );
    
            return $shadowbox;
    
                            
        } else {
            return "&nbsp;";
        }
        
        
    }
    

    
    /**
     * Check if we're on an ipad, iphone or android device..
     *
     * @return boolean
     */
    public function twig_ismobileclient() 
    {
    
        if(preg_match('/(android|blackberry|htc|iemobile|iphone|ipad|ipaq|ipod|nokia|playbook|smartphone)/i', 
            $_SERVER['HTTP_USER_AGENT'])) {
            return true;       
        } else {
            return false;        
        }
    }
    
    
    
    /**
     * Output a menu..
     *
     */
    public function menu(Twig_Environment $env, $identifier = "") 
    {
        global $app;
        
        $menus = $app['config']['menu'];
        
        if (!empty($identifier) && isset($menus[$identifier]) ) {
            $menu = $menus[$identifier];
        } else {
            $menu = util::array_first($menus);
        }
        
        // if the item is like 'content/1', get that content.
        foreach ($menu as $key=>$item) {
            if (isset($item['content'])) {
                
                $content = $app['storage']->getSingleContent($item['content']);
                // echo "<pre>\n" . util::var_dump($content, true) . "</pre>\n";
                $menu[$key]['label'] = !empty($content['title']) ? $content['title'] : $content['name'];
                $menu[$key]['title'] = !empty($content['subtitle']) ? $content['subtitle'] : "";
            }
        }
        
        //echo "<pre>\n" . util::var_dump($menu, true) . "</pre>\n";
        
        echo $env->render('_sub_menu.twig', array('menu' => $menu));
                    


    }
    
        
    
}




