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
        'version' => "0.5",
        'required_bolt_version' => "0.8",
        'highest_bolt_version' => "0.8",
        'type' => "Twig function",
        'first_releasedate' => "2012-10-10",
        'latest_releasedate' => "2012-10-19",
    );

    return $data;

}

function init($app)
{

    // Make sure the css is inserted as well..
    $app['extensions']->addCSS( $app['paths']['app'] . "extensions/SimpleForms/assets/simpleforms.css");

    $app['twig']->addFunction('simpleform', new \Twig_Function_Function('SimpleForms\simpleform'));

}


use Symfony\Component\Validator\Constraints as Assert;

function simpleform($name="")
{
    global $app;

    // Get the config file.
    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    // Select which form to use..
    if (isset($config[$name])) {
        $formconfig = $config[$name];
    } else {
        return "Simpleforms: No form known by name '$name'.";
    }

    $form = $app['form.factory']->createBuilder('form');

    foreach ($formconfig['fields'] as $name => $field) {

        $options = array();

        if (!empty($field['label'])) {
            $options['label'] = $field['label'];
        }
        if (!empty($field['placeholder'])) {
            $options['attr']['placeholder'] = $field['placeholder'];
        }
        if (!empty($field['class'])) {
            $options['attr']['class'] = $field['class'];
        }

        // Make sure $field has a type, or the form will break.
        if (empty($field['type'])) {
            $field['type'] = "text";
        } else if ($field['type']=="email") {
            $options['constraints'][] = new Assert\Email();
        }

        $form->add($name, $field['type'], $options);

    }

    $form = $form->getForm();

    $message = "";
    $error = "";
    $sent = false;

    if ('POST' == $app['request']->getMethod()) {
        $form->bind($app['request']);

        if ($form->isValid()) {
            $data = $form->getData();

            $mailhtml = $app['twig']->render("SimpleForms/".$config['mail_template'], array(
                    'form' =>  $data ));

            // echo "<pre>\n" . \util::var_dump($mailhtml, true) . "</pre>\n";

            $message = \Swift_Message::newInstance()
                ->setSubject('[SimpleForms] ' . $name )
                ->setFrom(array($formconfig['recipient_email'] => $formconfig['recipient_name']))
                ->setTo(array($formconfig['recipient_email'] => $formconfig['recipient_name']))
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html');

            $res = $app['mailer']->send($message);

            if ($res) {
                $message = $config['message_ok'];
                $sent = true;
            } else {
                $error = "There was an error sending the email. Alert the site administrator, and have them check the settings.";
            }

        } else {

            $error = $config['message_error'];

        }
    }

    $app['twig.path'] = __DIR__;


    $formhtml = $app['twig']->render("SimpleForms/".$config['template'], array(
        "submit" => "Send",
        "form" => $form->createView(),
        "message" => $message,
        "error" => $error,
        "sent" => $sent
    ));

    return $formhtml;

}





