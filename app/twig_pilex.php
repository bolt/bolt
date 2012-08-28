<?php

$app['twig']->addFunction('print', new Twig_Function_Function('twig_print', array('is_safe' => array('html'))));

function twig_print($var) {
        
    $output = util::var_dump($var, true);
    
    return $output;
    
}


$app['twig']->addFunction('excerpt', new Twig_Function_Function('twig_excerpt'));
$app['twig']->addFilter('excerpt', new Twig_Filter_Function('twig_excerpt'));

function twig_excerpt($content, $length=200) {

    
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
        
        unset($content['id'], $content['slug'], $content['datecreated'], $content['datechanged'], $content['username'], $content['title'], $content['contenttype'], $content['status'], $content['taxonomy']);  
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


$app['twig']->addFunction('trimtext', new Twig_Function_Function('twig_trim'));
$app['twig']->addFilter('trimtext', new Twig_Filter_Function('twig_trim'));

function twig_trim($content, $length=200) {

    $output = trimText(strip_tags($content), $length) ;
    
    return $output;
    
}


$app['twig']->addFilter('ucfirst', new Twig_Filter_Function('twig_ucfirst'));

function twig_ucfirst($str, $param="") {
    
    return ucfirst($str);
    
}


$app['twig']->addFilter('link', new Twig_Filter_Function('twig_link'));
$app['twig']->addFunction('link', new Twig_Function_Function('twig_link'));

function twig_link($content, $param="") {
    
    // TODO: use Silex' UrlGeneratorServiceProvider instead.
    $link = sprintf("/%s/%s", $content['contenttype']['singular_slug'], $content['slug']);
    
    return $link;
    
}




$app['twig']->addFilter('current', new Twig_Filter_Function('twig_current'));
$app['twig']->addFunction('current', new Twig_Function_Function('twig_current'));

function twig_current($content, $param="") {
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







$app['twig']->addFunction('token', new Twig_Function_Function('twig_token'));

/**
 * get a simple CSRF-like token
 *
 * @see getToken()
 * @return string 
 */
function twig_token() {
        
    return getToken();
    
}



$app['twig']->addFunction('listtemplates', new Twig_Function_Function('twig_listtemplates'));

/**
 * lists templates, optionallyfiltered by $filter
 *
 * @param string $filter
 * @return string 
 */
function twig_listtemplates($filter="") {
        
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





$app['twig']->addFunction('showpager', new Twig_Function_Function('twig_showpager', array('needs_environment' => true)));

/**
 * output a simple pager, for paged pages.
 *
 * @param array $pager
 * @return string HTML
 */
function twig_showpager(Twig_Environment $env, $pager, $surr=4, $class="") {
        
    echo $env->render('_sub_pager.twig', array('pager' => $pager, 'surr' => $surr, 'class' => $class));
        
}




$app['twig']->addFunction('max', new Twig_Function_Function('twig_max'));

/**
 * return the 'max' of two values..
 *
 * @param mixed $a
 * @param mixed $b
 * @return mixed
 */
function twig_max($a, $b) {
    return max($a, $b);        
}




$app['twig']->addFunction('min', new Twig_Function_Function('twig_min'));

/**
 * return the 'min' of two values..
 *
 * @param mixed $a
 * @param mixed $b
 * @return mixed
 */
function twig_min($a, $b) {
    return min($a, $b);        
}





$app['twig']->addFunction('request', new Twig_Function_Function('twig_request'));

/**
 * return the requested parameter from $_REQUEST, $_GET or $_POST..
 *
 * @param string $parameter
 * @param string $first
 * @return mixed
 */
function twig_request($parameter, $first="") {

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





// Stubs for the 'trans' and 'transchoice' filters.
$app['twig']->addFilter('trans', new Twig_Filter_Function('twig_trans'));
$app['twig']->addFilter('transchoice', new Twig_Filter_Function('twig_trans'));

function twig_trans($str) {
        return $str;
}


$app['twig']->addFilter('thumbnail', new Twig_Filter_Function('twig_thumbnail'));

function twig_thumbnail($filename, $width=100, $height=100, $crop="") {
    
    $thumbnail = sprintf("%s/%sx%s%s/%s", 
        "/thumbs",
        $width, 
        $height,
        $crop,
        $filename
        );
    
    return $thumbnail;
    
}



$app['twig']->addFilter('shadowbox', new Twig_Filter_Function('twig_shadowbox', array('is_safe' => array('html'))));

function twig_shadowbox($filename="", $width=100, $height=100, $crop="") {
    
    if (!empty($filename)) {

        $thumbnail = twig_thumbnail($filename, $width, $height, $crop);
        $large = twig_thumbnail($filename, 1000, 1000, 'r');
    
        $shadowbox = sprintf('<a href="%s" rel="shadowbox" title="Image: %s">
                <img src="%s" width="%s" height="%s"></a>', 
                $large, $filename, $thumbnail, $width, $height );

        return $shadowbox;

                        
    } else {
        return "&nbsp;";
    }
    
    
}


/**
 * Get 'content' 
 *
 * 
 * usage:
 * To get 10 entries, in order: {% set entries = content('entries', {'limit': 10}) %}
 * 
 * To get the latest single page: {% set page = content('page', {'order':'datecreated desc'}) %}
 *
 * To get 5 upcoming events: {% set events = content('events', {'order': 'date asc', 'where' => 'date < now()' }) %}
 */
$app['twig']->addFunction('content', new Twig_Function_Function('twig_content'));

function twig_content($contenttypeslug, $params) {
    global $app; /* TODO: figure out if there's a way to do this without globals.. */

    $contenttype = $app['storage']->getContentType($contenttypeslug);
        
    // If the contenttype doesn't exist, return an empty array
    if (!$contenttype) {
        $app['monolog']->addWarning("contenttype '$contenttypeslug' doesn't exist.");
        return array();
    }
    
    if ((makeSlug($contenttypeslug) == $contenttype['singular_slug']) || $params['limit']==1) {
        // If we used the singular version of the contenttype, or we specifically request only one result.. 
        $content = $app['storage']->getSingleContent($contenttypeslug, $params);
    } else {
        // Else, we get more than one result
        $content = $app['storage']->getContent($contenttypeslug, $params);
    }
    
    return $content; 
    
}



$app['twig']->addFunction('ismobileclient', new Twig_Function_Function('twig_ismobileclient'));

/**
 * Check if we're on an ipad, iphone or android device..
 *
 * @return boolean
 */
function twig_ismobileclient() {

    if(preg_match('/(android|blackberry|htc|iemobile|iphone|ipad|ipaq|ipod|nokia|playbook|smartphone)/i', 
        $_SERVER['HTTP_USER_AGENT'])) {
        return true;       
    } else {
        return false;        
    }
}



