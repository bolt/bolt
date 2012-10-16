<?php
// Simple forms Extension for Bolt

namespace SimpleForms;

function info()
{

    $data = array(
        'name' =>"Simple Forms",
        'description' => "This extension will allow you to insert simple forms on your site, for users to get in touch, send you a quick note or something like that. To use, configure the required fields in config.yml, and place <code>{{ simpleform() }}</code> in your templates.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => 0.5,
        'required_bolt_version' => 0.8,
        'type' => "Twig function",
        'releasedate' => "2012-10-15"
    );

    return $data;

}

function init($app)
{

    $app['twig']->addFunction('simpleform', new \Twig_Function_Function('SimpleForms\simpleform'));

}




function simpleform($form="")
{
    global $app;

    // Get the config file.
    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    // Select which form to use..
    if (isset($config[$name])) {
        $form = $config[$name];
    } else {
        $form = current($config);
    }

    echo "<pre>\n" . \util::var_dump($form, true) . "</pre>\n";



    // some default data for when the form is displayed the first time
    $data = array(
        'name' => 'Your name',
        'email' => 'Your email',
    );

    $form = $app['form.factory']->createBuilder('form', $data)
        ->add('name')
        ->add('email')
        ->add('gender', 'choice', array(
        'choices' => array(1 => 'male', 2 => 'female'),
        'expanded' => true,
    ))
        ->getForm();

    if ('POST' == $app['request']->getMethod()) {
        $form->bind($app['request']);

        if ($form->isValid()) {
            $data = $form->getData();

            // do something with the data

            echo "<pre>DATA!!! \n" . \util::var_dump($data, true) . "</pre>\n";

            // redirect somewhere
            //return $app->redirect('...');
        }
    }

    $app['twig.path'] = __DIR__;


    $formhtml = $app['twig']->render("SimpleForms/form.twig", array(
        "submit" => "Send",
        "form" =>  $form->createView())
    );

    return $formhtml;

}





