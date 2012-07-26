<?php

$app['twig']->addFunction('printr', new Twig_Function_Function('twig_printr', array('is_safe' => array('html'))));

function twig_printr($var, $skippre=false) {
    
    /*
    $output = "";
    
    if (!$skippre) {
        $output .= "<pre class='printr'>\n";
    }
    $output .= print_r($var, true);
    if (!$skippre) {
        $output .= "</pre>\n";
    }
    */
    
    $output = util::var_dump($var, true);
    
    return $output;
    
}


$app['twig']->addFunction('excerpt', new Twig_Function_Function('twig_excerpt'));
$app['twig']->addFilter('excerpt', new Twig_Filter_Function('twig_excerpt'));

function twig_excerpt($content, $length=200) {

    if (is_array($content)) {
        unset($content['id'], $content['slug'], $content['datecreated'], $content['datechanged'], $content['username'], $content['title'], $content['contenttype'], $content['status'], $content['taxonomy']);  
        $content = implode(" ", $content);
    }
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
