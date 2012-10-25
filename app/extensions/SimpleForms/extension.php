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
        'version' => "0.6",
        'required_bolt_version' => "0.7.9",
        'highest_bolt_version' => "0.7.9",
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

/**
 * Create a simple Form.
 *
 * @param string $name
 * @return string
 */
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
        return "Simpleforms notice: No form known by name '$name'.";
    }

    // Set the button text.
    $button_text = "Send";
    if (!empty($formconfig['button_text'])) {
        $button_text = $formconfig['button_text'];
    } elseif (!empty($config['button_text'])) {
        $button_text = $config['button_text'];
    }

    $message = "";
    $error = "";
    $sent = false;

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
        if (!empty($field['required']) && $field['required'] == true) {
            $options['required'] = true;
        } else {
            $options['required'] = false;
        }
        if (!empty($field['choices']) && is_array($field['choices'])) {
            // Make the keys more sensible.
            $options['choices'] = array();
            foreach ($field['choices'] as $option) {
                $options['choices'][ safeString($option)] = $option;
            }
        }

        // Make sure $field has a type, or the form will break.
        if (empty($field['type'])) {
            $field['type'] = "text";
        } elseif ($field['type']=="email") {
            $options['constraints'][] = new Assert\Email();
        }

        $form->add($name, $field['type'], $options);

    }

    $form = $form->getForm();

    if ('POST' == $app['request']->getMethod()) {
        $form->bind($app['request']);

        if ($form->isValid()) {
            $data = $form->getData();

            // $data contains the posted data. For legibility, change boolean fields to "yes" or "no".
            foreach($data as $key => $value) {
                if (gettype($value)=="boolean") {
                    $data[$key] = ($value ? "yes" : "no");
                }
            }

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
                $error = $config['message_technical'];
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
        "sent" => $sent,
        "button_text" => $button_text
    ));

    return $formhtml;

}





