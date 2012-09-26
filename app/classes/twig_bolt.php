<?php

/**
 * The class for Bolt' Twig tags, functions and filters.
 */
class Bolt_Twig_Extension extends Twig_Extension
{
    public function getName()
    {
        return 'Bolt';
    }
    
    public function getFunctions()
    {
        return array(
            'print' => new Twig_Function_Method($this, 'print_dump', array('is_safe' => array('html'))),
            'excerpt' => new Twig_Function_Method($this, 'excerpt'),
            'trimtext' => new Twig_Function_Method($this, 'trim'),
            'current' => new Twig_Function_Method($this, 'current'),
            'token' => new Twig_Function_Method($this, 'token'),
            'listtemplates' => new Twig_Function_Method($this, 'listtemplates'),
            'pager' => new Twig_Function_Method($this, 'pager', array('needs_environment' => true)),
            'max' => new Twig_Function_Method($this, 'max'),
            'min' => new Twig_Function_Method($this, 'min'),
            'request' => new Twig_Function_Method($this, 'request'),
            'content' => new Twig_Function_Method($this, 'content'),
            'ismobileclient' => new Twig_Function_Method($this, 'ismobileclient'),
            'menu' => new Twig_Function_Method($this, 'menu', array('needs_environment' => true)),  
            'randomquote' => new Twig_Function_Method($this, 'randomquote'),  
        );
    }    
  
  
    public function getFilters()
    {
        return array(
            'rot13' => new Twig_Filter_Method($this, 'rot13Filter'),
            'trimtext' => new Twig_Filter_Method($this, 'trim'),
            'ucfirst' => new Twig_Filter_Method($this, 'ucfirst'),
            'excerpt' => new Twig_Filter_Method($this, 'excerpt'),
            'current' => new Twig_Filter_Method($this, 'current'),
            'trans' => new Twig_Filter_Method($this, 'trans'),
            'transchoice' => new Twig_Filter_Method($this, 'trans'),
            'thumbnail' => new Twig_Filter_Method($this, 'thumbnail'),
            'shadowbox' => new Twig_Filter_Method($this, 'shadowbox', array('is_safe' => array('html'))),
            'editable' => new Twig_Filter_Method($this, 'editable', array('is_safe' => array('html'))),

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
    public function print_dump($var) 
    {
        
        $output = util::var_dump($var, true);
        
        return $output;
        
    }
    
    
    /**
     * Create an excerpt for the given content
     */
    public function excerpt($content, $length=200)
    {
    
        // If it's an contenct object, let the object handle it.
        if (is_object($content)) {
            return $content->excerpt($length);    
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
    public function trim($content, $length=200) 
    {
    
        $output = trimText(strip_tags($content), $length) ;
        
        return $output;
        
    }
    
    
    /**
     * UCfirsts the given string.
     */
    public function ucfirst($str, $param="") 
    {
        
        return ucfirst($str);
        
    }

    
    /** 
     * Returns true, if the given content is the current content.
     *
     * If we're on page/foo, and content is that page, you can use 
     * {% is page|current %}class='active'{% endif %}
     */
    public function current($content, $param="") 
    {
        global $app;
        
        $route_params = $app['request']->get('_route_params');  

        // check against $_SERVER['REQUEST_URI']
        if( $_SERVER['REQUEST_URI'] == $content['link'] ) {
            return true;
        }
        
        // No contenttypeslug or slug -> not 'current'
        if (empty($route_params['contenttypeslug']) || empty($route_params['slug'])) {
            return false;
        }
        
        // check against simple content.link
        if( "/".$route_params['contenttypeslug']."/".$route_params['slug'] == $content['link'] ) {
            return true;
        }              
        
        // if the current requested page is for the same slug or singularslug..
        if (isset($content['contenttype']) &&
            ($route_params['contenttypeslug'] == $content['contenttype']['slug'] ||
            $route_params['contenttypeslug'] == $content['contenttype']['singular_slug']) ) {
            
            // .. and the slugs should match..
            if ($route_params['slug'] == $content['slug']) {
                echo "joe!";
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
    public function token() 
    {
            
        return getToken();
        
    }
    
    
    /**
     * lists templates, optionally filtered by $filter
     *
     * @param string $filter
     * @return string 
     */
    public function listtemplates($filter="") 
    {
        global $app;

        $files = array();
    
        $foldername = realpath(__DIR__.'/../../theme/' . $app['config']['general']['theme']);


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
                          
                $files[$file] = $file;       
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
    public function pager(Twig_Environment $env, $pagername='', $surr=4, $template='_sub_pager.twig', $class='')
    {
        // Yuck, $GLOBALS.. TODO: figure out a better way to do this.
        $pager = $GLOBALS['pager'];

        if (!is_array($pager)) {
            // nothing to page..
            return "";
        }

        if (!empty($pagername)) {
            $thispager = $pager[$pagername];
        } else {
            $thispager = array_pop($pager);
        }

        echo $env->render($template, array('pager' => $thispager, 'surr' => $surr, 'class' => $class));
            
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
    public function content($contenttypeslug, $params) 
    {
        global $app; /* TODO: figure out if there's a way to do this without globals.. */
    
        $contenttype = $app['storage']->getContentType($contenttypeslug);
            
        // If the contenttype doesn't exist, return an empty array
        if (!$contenttype) {
            $app['log']->add("contenttype '$contenttypeslug' doesn't exist.", 1);
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
    public function max($a, $b) 
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
    public function min($a, $b) 
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
    public function request($parameter, $first="") 
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
    public function trans($str) 
    {
            return $str;
    }
    
    
    /**
     * Helper function to make a path to an image thumbnail.
     *
     */
    public function thumbnail($filename, $width=100, $height=100, $crop="") 
    {
        global $app;

        $thumbnail = sprintf("%sthumbs/%sx%s%s/%s",
            $app['paths']['root'],
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
    public function shadowbox($filename="", $width=100, $height=100, $crop="") 
    {
        
        if (!empty($filename)) {
    
            $thumbnail = $this->thumbnail($filename, $width, $height, $crop);
            $large = $this->thumbnail($filename, 1000, 1000, 'r');
        
            $shadowbox = sprintf('<a href="%s" rel="shadowbox" title="Image: %s">
                    <img src="%s" width="%s" height="%s"></a>', 
                    $large, $filename, $thumbnail, $width, $height );
    
            return $shadowbox;
    
                            
        } else {
            return "&nbsp;";
        }
        
        
    }
    

    

    public function editable($html, $content, $field) 
    {
        
        $contenttype = $content->contenttype['slug'];
        $id = $content->id;
        
        $output = sprintf("<div class='Bolt-editable' data-id='%s' data-contenttype='%s' data-field='%s'>%s</div>", 
            $content->id,
            $contenttype,
            $field,
            $html
            );
        
        return $output;
        
        
    }
    


    
    /**
     * Check if we're on an ipad, iphone or android device..
     *
     * @return boolean
     */
    public function ismobileclient() 
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
            $name = strtolower($identifier);
            $menu = $menus[$identifier];
        } else {
            $name = strtolower(util::array_first_key($menus));
            $menu = util::array_first($menus);
        }
        
        
        foreach ($menu as $key=>$item) {
            $menu[$key] = $this->menu_helper($item);
            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as $subkey=>$subitem) {
                   $menu[$key]['submenu'][$subkey] = $this->menu_helper($subitem); 
               }
            }          
            
        }


        // echo "<pre>\n" . util::var_dump($menu, true) . "</pre>\n";
        
        echo $env->render('_sub_menu.twig', array('name' => $name, 'menu' => $menu));
                    


    }
    
    
    private function menu_helper($item) 
    {
        global $app;

        if (isset($item['path']) && $item['path'] == "homepage") {
            $item['link'] = $app['paths']['root'];
        } else if (isset($item['path'])) {

            // if the item is like 'content/1', get that content.

            $content = $app['storage']->getSingleContent($item['path']);
            
            if (empty($item['label'])) {
                $item['label'] = !empty($content->values['title']) ? $content->values['title'] : $content->values['title'];                
            }
            if (empty($item['title'])) {
                $item['title'] = !empty($content->values['subtitle']) ? $content->values['subtitle'] : "";
            }
            if (is_object($content)) {
                $item['link'] = $content->link();
            }
        }



        return $item;
        
    }
        
        
    public function randomquote() 
    {
        $quotes = array(
            "Complexity is your enemy. Any fool can make something complicated. It is hard to make something simple.#Richard Branson",
            "It takes a lot of effort to make things look effortless.#Mark Pilgrim",
            "Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.#Antoine de Saint-Exupéry",
            "Everything should be made as simple as possible, but not simpler.#Albert Einstein",
            "Three Rules of Work: Out of clutter find simplicity; From discord find harmony; In the middle of difficulty lies opportunity.#Albert Einstein",
            "There is no greatness where there is not simplicity, goodness, and truth.#Leo Tolstoy",
            "Think simple as my old master used to say - meaning reduce the whole of its parts into the simplest terms, getting back to first principles.#Frank Lloyd Wright",
            "Simplicity is indeed often the sign of truth and a criterion of beauty.#Mahlon Hoagland",
            "Simplicity and repose are the qualities that measure the true value of any work of art.#Frank Lloyd Wright",
            "Nothing is true, but that which is simple.#Johann Wolfgang von Goethe",
            "There is a simplicity that exists on the far side of complexity, and there is a communication of sentiment and attitude not to be discovered by careful exegesis of a text.#Patrick Buchanan",
            "The simplest things are often the truest.#Richard Bach",
            "If you can't explain it to a six year old, you don't understand it yourself.#Albert Einstein",
            "One day I will find the right words, and they will be simple.#Jack Kerouac",
            "Simplicity is the ultimate sophistication.#Leonardo da Vinci",
            "Our life is frittered away by detail. Simplify, simplify.#Henry David Thoreau",
            "The simplest explanation is always the most likely.#Agatha Christie",
            "Truth is ever to be found in the simplicity, and not in the multiplicity and confusion of things.#Isaac Newton",
            "Simplicity is a great virtue but it requires hard work to achieve it and education to appreciate it. And to make matters worse: complexity sells better.#Edsger Wybe Dijkstra",
            "Focus and simplicity. Simple can be harder than complex: You have to work hard to get your thinking clean to make it simple. But it's worth it in the end because once you get there, you can move mountains.#Steve Jobs",
            "The ability to simplify means to eliminate the unnecessary so that the necessary may speak.#Hans Hofmann",
            "I've learned to keep things simple. Look at your choices, pick the best one, then go to work with all your heart.#Pat Riley",
            "A little simplification would be the first step toward rational living, I think.#Eleanor Roosevelt",
            "Making the simple complicated is commonplace; making the complicated simple, awesomely simple, that's creativity.#Charles Mingus"
        );
        
        $randomquote = explode("#", $quotes[array_rand($quotes, 1)]);
        
        $quote = sprintf("“%s”\n<cite>— %s</cite>", $randomquote[0], $randomquote[1]);
            
        return $quote;        

        
    }
    
}




