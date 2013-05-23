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
            'version' => "1.4",
            'required_bolt_version' => "1.1",
            'highest_bolt_version' => "1.1",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-05-15",
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
        if (empty($this->config['button_text'])) {
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
    function simpleForm($formname = "")
    {

        $this->app['twig.loader.filesystem']->addPath(__DIR__);

        // Select which form to use..
        if (isset($this->config[$formname])) {
            $formconfig = $this->config[$formname];
        } else {
            return "Simpleforms notice: No form known by name '$formname'.";
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

        $form = $this->app['form.factory']->createBuilder('form');

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
                $options['constraints'][] = new Assert\NotBlank();
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
            } elseif ($field['type'] == "email") {
                // if the field is email, check for a valid email address
                $options['constraints'][] = new Assert\Email();
            } elseif ($field['type'] == "file") {
                // if the field is file, make sure we set the accept properly.
                $accept = array();

                // Don't accept _all_ types. If nothing set in config.yml, set some sensilbe defaults.
                if (empty($field['filetype'])) {
                    $field['filetype'] = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx');
                }
                foreach ($field['filetype'] as $ext) {
                    $accept[] = ".".$ext;
                }
                $options['attr']['accept'] = implode(",", $accept);
            }

            // Yeah, this feels a bit flakey, but I'm not sure how I can get the form type in the template
            // in another way.
            $options['attr']['type'] = $field['type'];

            $form->add($name, $field['type'], $options);

        }

        $form = $form->getForm();

        if ('POST' == $this->app['request']->getMethod()) {
            $form->bind($this->app['request']);

            if ($form->isValid()) {

                $res = $this->processForm($formconfig, $form, $formname);

                if ($res) {
                    $message = $formconfig['message_ok'];
                    $sent = true;

                    // If redirect_on_ok is set, redirect to that page when succesful.
                    if (!empty($formconfig['redirect_on_ok'])) {
                        $content = $this->app['storage']->getContent($formconfig['redirect_on_ok']);
                        simpleredirect($content->link(), false);
                    }

                } else {
                    $error = $formconfig['message_technical'];
                }

            } else {

                $error = $formconfig['message_error'];

            }
        }


        $formhtml = $this->app['twig']->render($formconfig['template'], array(
            "submit" => "Send",
            "form" => $form->createView(),
            "message" => $message,
            "error" => $error,
            "sent" => $sent,
            "formname" => $formname,
            "button_text" => $formconfig['button_text']
        ));

        return new \Twig_Markup($formhtml, 'UTF-8');

    }



    private function processForm($formconfig, $form, $formname)
    {

        $data = $form->getData();

        // $data contains the posted data. For legibility, change boolean fields to "yes" or "no".
        foreach($data as $key => $value) {
            if (gettype($value)=="boolean") {
                $data[$key] = ($value ? "yes" : "no");
            }
        }

        // Check if we have fields of type 'file'. If so, fetch them, and move them
        // to the designated folder.
        foreach ($formconfig['fields'] as $fieldname => $fieldvalues) {
            if ($fieldvalues['type'] == "file") {
                if (empty($fieldvalues['storage_location'])) {
                    die("You must set the storage_location in the field $fieldname.");
                }
                $path = __DIR__ . "/" . $fieldvalues['storage_location'];
                if (!is_writable($path)) {
                    die("The path $path is not writable.");
                }
                $files = $this->app['request']->files->get($form->getName());
                $originalname = strtolower($files[$fieldname]->getClientOriginalName());
                $filename = sprintf("%s-%s-%s.%s", $fieldname, date('Y-m-d'), makeKey(8), getExtension($originalname));
                $link = sprintf("%sapp/extensions/SimpleForms/%s/%s", $this->app['paths']['rooturl'], $fieldvalues['storage_location'], $filename);

                // Make sure the file is in the allowed extensions.
                if (in_array(getExtension($originalname), $fieldvalues['filetype'])) {
                    // If so, replace the file to designated folder.
                    $files[$fieldname]->move($path, $filename);
                    $data[$fieldname] = $link;
                } else {
                    $data[$fieldname] = "Invalid upload, ignored ($originalname)";
                }


            }
        }


        // Attempt to insert the data into a table, if specified..
        if (!empty($formconfig['insert_into_table'])) {
            try {
                $this->app['db']->insert($formconfig['insert_into_table'], $data);
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
                echo "Couldn't insert data into table " . $formconfig['insert_into_table'] . ".";
            }

        }

        $mailhtml = $this->app['twig']->render($formconfig['mail_template'], array(
            'form' =>  $data ));

        // \util::var_dump($mailhtml);

        if (!empty($formconfig['mail_subject'])) {
            $subject = $formconfig['mail_subject'];
        } else {
            $subject = '[SimpleForms] ' . $formname;
        }

        // Compile the message..
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(array($formconfig['recipient_email'] => $formconfig['recipient_name']))
            ->setTo(array($formconfig['recipient_email'] => $formconfig['recipient_name']))
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html');

        // If 'submitter_cc' is set, add a 'cc' to the submitter of the form.
        if (!empty($formconfig['submitter_cc'])) {
            if (isEmail($formconfig['submitter_cc'])) {
                $address = $formconfig['submitter_cc'];
            } else if (!empty($data[$formconfig['submitter_cc']])) {
                $address = $data[$formconfig['submitter_cc']];
            }
            $message->setCC($address);
        }

        $res = $this->app['mailer']->send($message);

        return $res;

    }

}
