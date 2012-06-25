<?php

$app['twig']->addFunction('printr', new Twig_Function_Function('twig_printr', array('is_safe' => array('html'))));

function twig_printr($var, $skippre=false) {
    
    $output = "";
    
    if (!$skippre) {
        $output .= "<pre class='printr'>\n";
    }
    $output .= print_r($var, true);
    if (!$skippre) {
        $output .= "</pre>\n";
    }
    
    return $output;
    
}


$app['twig']->addFunction('excerpt', new Twig_Function_Function('twig_excerpt'));

function twig_excerpt($content, $length=200) {

    unset($content['id'], $content['slug'], $content['datecreated'], $content['datechanged'], $content['username'], $content['title']);  
    
    $output = implode(" ", $content);
    $output = trimText(strip_tags($output), $length) ;
    
    return $output;
    
}

$app['twig']->addFilter('ucfirst', new Twig_Filter_Function('twig_ucfirst'));

function twig_ucfirst($str, $param="") {
    
    return ucfirst($str);
    
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

