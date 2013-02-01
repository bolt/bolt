<?php
// Simple forms Extension for Bolt

namespace SimpleForms;

use Symfony\Component\Validator\Constraints as Assert;

class Extension extends \Bolt\BaseExtension
{

    private $global_fields;
    private $text_labels;
    private $labelsenabled;

    function info()
    {

        $data = array(
            'name' =>"Simple Forms",
            'description' => "This extension will allow you to insert simple forms on your site, for users to get in touch, send you a quick note or something like that. To use, configure the required fields in config.yml, and place <code>{{ simpleform('contact') }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.2",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.0",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-02-01",
        );

        return $data;

    }

    function initialize()
    {
        if (empty($this->config['stylesheet'])) { $this->config['stylesheet'] = "assets/simpleforms.css"; }
        
        // fields that the global config should have
        $this->global_fields = array(
                            'stylesheet',
                            'template',
                            'mail_template',
                            'message_ok',
                            'message_error',
                            'message_technical',
                            'button_text'
        );
        
        // labels to translate
        $this->text_labels = array(
                            'message_ok',
                            'message_error',
                            'message_technical',
                            'button_text',
                            'label',
                            'placeholder'
        );

        // Make sure the css is inserted as well..
        $this->addCSS($this->config['stylesheet']);

        // Set the button text.
        if (!empty($this->config['button_text'])) {
            $this->config['button_text'] = "Send";
        }

        $this->addTwigFunction('simpleform', 'simpleForm');

    }



    /**
     * Create a simple Form.
     *
     * @param string $name
     * @return string
     */
    function simpleForm($name="")
    {
        global $app;

        // Select which form to use..
        if (isset($this->config[$name])) {
            $formconfig = $this->config[$name];
        } else {
            return "Simpleforms notice: No form known by name '$name'.";
        }

        // Set the mail configuration for empty fields to the global defaults.
        foreach($this->global_fields as $configkey) {
            if (empty($formconfig[$configkey])) {
                $formconfig[$configkey] = $this->config[$configkey];
            }
        }
        
        // tanslate labels if labels extension exists
        if($this->labelsenabled) {
            $this->labelfields($formconfig);
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
            if (!empty($field['expanded'])) {
                $options['expanded'] = $field['expanded'];
            }
            if (!empty($field['multiple'])) {
                $options['multiple'] = $field['multiple'];
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

                $mailhtml = $app['twig']->render("SimpleForms/".$formconfig['mail_template'], array(
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
                    $message = $formconfig['message_ok'];
                    $sent = true;
                } else {
                    $error = $formconfig['message_technical'];
                }

            } else {

                $error = $formconfig['message_error'];

            }
        }

        $app['twig.path'] = __DIR__;


        $formhtml = $app['twig']->render("SimpleForms/".$formconfig['template'], array(
            "submit" => "Send",
            "form" => $form->createView(),
            "message" => $message,
            "error" => $error,
            "sent" => $sent,
            "button_text" => $formconfig['button_text']
        ));

        return new \Twig_Markup($formhtml, 'UTF-8');

    }


}




