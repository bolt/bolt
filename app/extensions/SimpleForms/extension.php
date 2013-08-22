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
            'version' => "1.7",
            'required_bolt_version' => "1.1",
            'highest_bolt_version' => "1.1",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-08-14",
        );

        return $data;

    }

    function initialize()
    {

        // fields that the global config should have
        $this->global_fields = array(
            'stylesheet',
            'template',
            'mail_template',
            'message_ok',
            'message_error',
            'message_technical',
            'button_text',
            'attach_files',
            'recipient_cc_email',
            'recipient_cc_name',
            'recipient_bcc_email',
            'testmode',
            'testmode_recipient',
            'debugmode',
            'insert_into_table'
        );
        // note that debugmode and insert_into_table are undocumented

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
        if (!empty($this->config['stylesheet'])) {
            $this->addCSS($this->config['stylesheet']);
        } else {
            $this->config['stylesheet'] = "";
        }

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

        // Set the mail configuration for empty fields to the global defaults if they exist
        foreach($this->global_fields as $configkey) {
            if (!array_key_exists($configkey, $formconfig) && !empty($this->config[$configkey])) {
                $formconfig[$configkey] = $this->config[$configkey];
            } elseif(!array_key_exists($configkey, $formconfig) && empty($this->config[$configkey])) {
                $formconfig[$configkey] = false;
            }
        }

        // tanslate labels if labels extension exists
        if($this->labelsenabled) {
            $this->labelfields($formconfig);
        }


        if($formconfig['debugmode']==true) {
            \util::var_dump($formconfig);
            \util::var_dump($formname);
            \util::var_dump($this->app['paths']);
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

            if (!empty($field['value'])) {
                $options['attr']['value'] = $field['value'];
            }

            if (!empty($field['allow_override']) && !empty($_GET[$name])) {
                $value = strip_tags($_GET[$name]); // Note Symfony's form also takes care of escaping this.
                $options['attr']['value'] = $value;
            }

            if (!empty($field['read_only'])) {
                $options['read_only'] = $field['read_only'];
            }

            if (!empty($field['placeholder'])) {
                $options['attr']['placeholder'] = $field['placeholder'];
            }

            if (!empty($field['class'])) {
                $options['attr']['class'] = $field['class'];
            }

            if (!empty($field['prefix'])) {
                $options['attr']['prefix'] = $field['prefix'];
            }
            if (!empty($field['postfix'])) {
                $options['attr']['postfix'] = $field['postfix'];
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

        if($formconfig['debugmode']==true) {
            \util::var_dump($formconfig);
            \util::var_dump($form);
            \util::var_dump($formname);
            \util::var_dump($data);
            \util::var_dump($this->app['request']->files);
        }

        // $data contains the posted data. For legibility, change boolean fields to "yes" or "no".
        foreach($data as $key => $value) {
            if (gettype($value)=="boolean") {
                $data[$key] = ($value ? "yes" : "no");
            }
        }

        // Some fieldtypes (like 'date' and 'file') require post-processing.
        foreach ($formconfig['fields'] as $fieldname => $fieldvalues) {

            // Check if we have fields of type 'file'. If so, fetch them, and move them
            // to the designated folder.
            if ($fieldvalues['type'] == "file") {
                if (empty($formconfig['storage_location']) && $formconfig['attach_files']===false) {
                    die("You must set the storage_location in the field $fieldname if you do not use attachments.");
                } elseif(empty($formconfig['storage_location']) && $formconfig['attach_files']==false) {
                    // temporary files location will be a subdirectory of the cache
                    $path = $this->app['paths']['apppath'] . '/cache';
                    $linkpath = $this->app['paths']['app'] . 'cache';
                } else {
                    // files location will be a subdirectory of the files
                    $path = $this->app['paths']['filespath'] . "/". $fieldvalues['storage_location'];
                    $linkpath = $this->app['paths']['files'] . $fieldvalues['storage_location'];
                }

                if (!is_writable($path)) {
                    die("The path $path is not writable.");
                }

                $files = $this->app['request']->files->get($form->getName());
                $originalname = strtolower($files[$fieldname]->getClientOriginalName());
                $filename = sprintf("%s-%s-%s.%s", $fieldname, date('Y-m-d'), makeKey(8), getExtension($originalname));
                $link = sprintf("%s%s/%s", $this->app['paths']['rooturl'], $linkpath, $filename);

                // Make sure the file is in the allowed extensions.
                if (in_array(getExtension($originalname), $fieldvalues['filetype'])) {
                    // If so, replace the file to designated folder.
                    $files[$fieldname]->move($path, $filename);
                    // by default we send a link
                    $data[$fieldname] = $link;

                    if($formconfig['attach_files'] == 'true') {
                        // if there is an attachment and no saved file on the server
                        // only send the original name and the attachment
                        if(empty($formconfig['storage_location'])) {
                            $data[$fieldname] = $originalname ." ($link)";
                        }
                        $attachments[] = \Swift_Attachment::fromPath($link)->setFilename($originalname);
                    }
                } else {
                    $data[$fieldname] = "Invalid upload, ignored ($originalname)";
                }
            }

            // Fields of type 'date' are \DateTime objects. Convert them to string, for sending in emails, etc.
            if (($fieldvalues['type'] == "date") && ($data[$fieldname] instanceof \DateTime)) {
                $format = isset($fieldvalues['format']) ? $fieldvalues['format'] : "Y-m-d";
                $data[$fieldname] = $data[$fieldname]->format($format);
            }

        }

        // Attempt to insert the data into a table, if specified..
        if (!empty($formconfig['insert_into_table'])) {
            try {
                $this->app['db']->insert($formconfig['insert_into_table'], $data);
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
                $keys = array_keys($data);
                $this->app['log']->add("SimpleForms could not insert data into table". $formconfig['insert_into_table'] . ' ('.join(', ', $keys).') - check if the table exists.', 3);
                echo "Couldn't insert data into table " . $formconfig['insert_into_table'] . ".";
            }
        }

        $mailhtml = $this->app['twig']->render($formconfig['mail_template'], array(
            'form' =>  $data ));

        if($formconfig['debugmode']==true) {
            \util::var_dump($mailhtml);
        }

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

        if(($formconfig['attach_files'] == 'true') && is_array($attachments)) {
            foreach($attachments as $attachment) {
                $message->attach($attachment);
            }
        }

        // check for testmode
        if($formconfig['testmode']==true) {
            // override recipient with debug recipient
            $message->setTo(array($formconfig['testmode_recipient'] => $formconfig['recipient_name']));

            // do not add other cc and bcc addresses in testmode
            if(!empty($formconfig['recipient_cc_email']) && $formconfig['recipient_email']!=$formconfig['recipient_cc_email']) {
                $this->app['log']->add('Did not set Cc for '. $formname . ' to '. $formconfig['recipient_cc_email'] . ' (in testmode)', 3);
            }
            if(!empty($formconfig['recipient_bcc_email']) && $formconfig['recipient_email']!=$formconfig['recipient_bcc_email']) {
                $this->app['log']->add('Did not set Bcc for '. $formname . ' to '. $formconfig['recipient_bcc_email'] . ' (in testmode)', 3);
            }
        } else {
            // only add other recipients when not in testmode
            if(!empty($formconfig['recipient_cc_email']) && $formconfig['recipient_email']!=$formconfig['recipient_cc_email']) {
                $message->setCc($formconfig['recipient_cc_email']);
                $this->app['log']->add('Added Cc for '. $formname . ' to '. $formconfig['recipient_cc_email'], 3);
            }
            if(!empty($formconfig['recipient_bcc_email']) && $formconfig['recipient_email']!=$formconfig['recipient_bcc_email']) {
                $message->setBcc($formconfig['recipient_bcc_email']);
                $this->app['log']->add('Added Bcc for '. $formname . ' to '. $formconfig['recipient_bcc_email'], 3);
            }

            // check for other email addresses to be added
            foreach($formconfig['fields'] as $key => $values) {
                if ($values['type']=="email" && in_array($values['use_as'], array('to_email', 'from_email', 'cc_email', 'bcc_email'))) {
                    $tmp_email = $data[$key];

                    if(isset($values['use_with'])) {
                        $tmp_name = $data[$values['use_with']];
                        if(!$tmp_name) {
                            $tmp_name = $tmp_email;
                        }
                    } else {
                        $tmp_name = $tmp_email;
                    }
                    switch($values['use_as']) {
                        case 'from_email':
                            // override from address
                            //$message->setSender($formconfig['recipient_email']); // just to be clear who really sent it
                            $message->setFrom(array($tmp_email => $tmp_name));
                            break;
                        case 'to_email':
                            // add another recipient
                            $message->addTo($tmp_email, $tmp_name);
                            break;
                        case 'cc_email':
                            // add a copy address
                            $message->addCc($tmp_email, $tmp_name);
                            break;
                        case 'bcc_email':
                            // add a blind copy address
                            $message->addBcc($tmp_email, $tmp_name);
                            break;
                    }
                }
            }
        }

        $res = $this->app['mailer']->send($message);

        if ($res) {
            if($formconfig['testmode']) {
                $this->app['log']->add('Sent email from '. $formname . ' to '. $formconfig['testmode_recipient'] . ' (in testmode) - ' . $formconfig['recipient_name'], 3);
            } else {
                $this->app['log']->add('Sent email from '. $formname . ' to '. $formconfig['recipient_email'] . ' - ' . $formconfig['recipient_name'], 3);
            }
        }

        return $res;

    }

}
