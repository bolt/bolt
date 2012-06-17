<?php

$app['twig']->addFunction('pompidom', new Twig_Function_Function('twig_pompidom'));
$app['twig']->addFunction('printr', new Twig_Function_Function('twig_printr', array('is_safe' => array('html'))));
$app['twig']->addFunction('excerpt', new Twig_Function_Function('twig_excerpt'));
$app['twig']->addFilter('ucfirst', new Twig_Filter_Function('twig_ucfirst'));
$app['twig']->addFunction('loadcontent', new Twig_Function_Function('twig_loadcontent', array('needs_environment' => true, 'is_safe' => array('html'))));


function twig_pompidom($str) {
    return "pom - pom - $str - pi - dom";
}


function twig_printr($var) {
    
    $output = "<pre class='printr'>\n";
    $output .= print_r($var, true);
    $output .= "</pre>\n";
    
    return $output;
    
}


function twig_excerpt($content, $length=200) {

    unset($content['id'], $content['slug'], $content['datecreated'], $content['datechanged'], $content['username'], $content['title']);  
    
    $output = implode(" ", $content);
    $output = trimText(strip_tags($output), $length) ;
    
    return $output;
    
}


function twig_ucfirst($str, $param="") {
    
    return ucfirst($str);
    
}

function twig_loadcontent(Twig_Environment $env, $string) {
 
    // $env->addGlobal("pompom", "tralalala");
    
    $content = array('title' => "que");
    
    $env->addGlobal("content", $content);
    
 
    return "loaden maar, die content";
    
}