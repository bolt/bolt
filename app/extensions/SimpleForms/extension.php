<?php
// Simple forms Extension for Bolt

/**
 * TODO: make the email-addresses better key=>value pairs
 */

namespace SimpleForms;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Filesystem\Filesystem;

class Extension extends \Bolt\AbstractExtension
{
    private $global_fields;
    private $text_labels;
    private $labelsenabled;

    public function info()
    {
        $data = array(
            'name' =>"Simple Forms",
            'description' => "This extension will allow you to insert simple forms on your site, for users to get in touch, send you a quick note or something like that. To use, configure the required fields in config.yml, and place <code>{{ simpleform('contact') }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.12",
            'required_bolt_version' => "1.6",
            'highest_bolt_version' => "1.6",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2014-06-24",
            'allow_in_user_content' => true,
        );
        return $data;
    }

    public function initialize()
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
            'from_email',
            'from_name',
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
        }
        else {
            $this->config['stylesheet'] = "";
        }

        // Make sure CSRF is set, unless disabled on purpose
        if (!isset($this->config['csrf'])) {
            $this->config['csrf'] = true;
        }

        // Set the button text.
        if (empty($this->config['button_text'])) {
            $this->config['button_text'] = "Send";
        }

        $this->addTwigFunction('simpleform', 'simpleForm');
    }

    private function buildField($name, $field, $with = array()) {
        $options = array();
        $options['required'] = false;

        $mappings = array(
                'label' => 'label',
                'value' => 'attr:value',
                'read_only' => 'read_only',
                'placeholder' => 'attr:placeholder',
                'class' => 'attr:class',
                'hint' => 'attr:hint',
                'required' => 'required',
                'prefix' => 'attr:prefix',
                'postfix' => 'attr:postfix',
                'empty_value' => 'empty_value',
                'maxlength' => 'attr:maxlength',
                'minlength' => 'attr:minlength',
                'autofocus' => 'attr:autofocus',
                'pattern' => 'attr:pattern',
                'autocomplete' => 'attr:autocomplete',
                'expanded' => 'expanded',
                'multiple' => 'multiple',
            );

        foreach ($mappings as $src => $dst) {
            if (!empty($field[$src])) {
                $value = $field[$src];
                $dstPath = explode(':', $dst);
                switch (count($dstPath)) {
                    case 1:
                        $options[$dstPath[0]] = $value; break;
                    case 2:
                        $options[$dstPath[0]][$dstPath[1]] = $value; break;
                    default:
                        throw \Exception("Invalid number of path components in $dstPath");
                }
            }
        }

        if (in_array($field['type'], array("ip", "remotehost", "useragent", "timestamp"))) {
            // we're storing IP, host, useragent and timestamp later.
            return null;
        }

        if (!empty($field['default'])) {
            $value = strip_tags($field['default']); // Note Symfony's form also takes care of escaping this.
            $options['data'] = $value;
        }

        if (!empty($with[$name])) {
            $value = strip_tags($with[$name]); // Note Symfony's form also takes care of escaping this.
            $options['attr']['value'] = $value;
        }

        if (!empty($field['allow_override']) && !empty($_GET[$name])) {
            $value = strip_tags($_GET[$name]); // Note Symfony's form also takes care of escaping this.
            $options['attr']['value'] = $value;
        }

        if (is_array($field['data'])) {
            foreach ($field['data'] as $datakey => $datavalue) {
                $options['attr']['data-'.$datakey] = $datavalue;
            }
        }

        if (!empty($field['role'])) {
            switch($field['role']) {
                case 'sequence':
                    // this is a sequential field
                    $options['attr']['data-role'] = $field['role'];
                    break;
                default:
                    // go away
                    break;
            }
        }

        if ($options['required']) {
            $options['constraints'][] = new Assert\NotBlank();
        }

        if (!empty($field['choices']) && is_array($field['choices'])) {
            // Make the keys more sensible.
            $options['choices'] = array();
            foreach ($field['choices'] as $key => $option) {
                $options['choices'][ $key ] = $option;
            }
        }

        // for optgroups
        if (!empty($field['optgroups']) && is_array($field['optgroups'])) {
            $options['choices'] = array();
            foreach ($field['optgroups'] as $key => $value) {
                $label   = $value['label'];
                $choices = array();

                if (is_array($value['choices'])) {
                    foreach ($value['choices'] as $k => $v) {
                        $choices[$k] = $v;
                    }
                }

                $options['choices'][$label] = $choices;
            }
        }

        // Make sure $field has a type, or the form will break.
        if (empty($field['type'])) {
            $type = "text";
        }
        else {
            $type = $field['type'];
        }

        if ($type === "email") {
            $options['constraints'][] = new Assert\Email();
        }
        if ($type === "file") {
            // if the field is file, make sure we set the accept properly.
            $accept = array();

            // Don't accept _all_ types. If nothing set in config.yml, set some sensible defaults.
            if (empty($field['filetype'])) {
                $field['filetype'] = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx');
            }
            foreach ($field['filetype'] as $ext) {
                $accept[] = ".".$ext;
            }
            $options['attr']['accept'] = implode(",", $accept);

            if (!empty($field['mimetype'])) {
                $options['constraints'][] = new Assert\File(array(
                            'mimeTypes' => $field['mimetype'],
                            'mimeTypesMessage' => $formconfig['mime_types_message'] . ' ' . implode(', ', $field['filetype']),
                            ));
            }
        }

        // Yeah, this feels a bit flaky, but I'm not sure how I can get
        // the form type in the template in another way.
        $options['attr']['type'] = $type;

        return $options;
    }

    /**
     * Create a simple Form.
     *
     * @param string $formname
     * @internal param string $name
     * @return string
     */
    public function simpleForm($formname = "", $with = array())
    {
        $this->app['twig.loader.filesystem']->addPath(__DIR__);

        // Select which form to use..
        if (isset($this->config[$formname])) {
            $formconfig = $this->config[$formname];
        }
        else {
            return "Simpleforms notice: No form known by name '$formname'.";
        }

        // Set the mail configuration for empty fields to the global defaults if they exist
        foreach($this->global_fields as $configkey) {
            if (!array_key_exists($configkey, $formconfig) && !empty($this->config[$configkey])) {
                $formconfig[$configkey] = $this->config[$configkey];
            }
            elseif(!array_key_exists($configkey, $formconfig) && empty($this->config[$configkey])) {
                $formconfig[$configkey] = false;
            }
        }

        // translate labels if labels extension exists
        if($this->labelsenabled) {
            $this->labelfields($formconfig);
        }

        if ($formconfig['debugmode']==true) {
            \Dumper::dump('Building '.$formname);
            \Dumper::dump($formconfig);
            //\Dumper::dump($this->app['paths']);
        }

        $message = "";
        $error = "";
        $sent = false;


        $form = $this->app['form.factory']->createNamedBuilder($formname, 'form', null, array('csrf_protection' => $this->config['csrf']));

        foreach ($formconfig['fields'] as $name => $field) {
            $options = $this->buildField($name, $field, $with);

            // only add known fields with options to the form
            if($options) {
                $form->add($name, $options['attr']['type'], $options);
            }
        }

        $form = $form->getForm();

        require_once('recaptcha-php-1.11/recaptchalib.php');

        if ('POST' == $this->app['request']->getMethod()) {
            if(!$this->app['request']->request->has($formname)) {
                // we're not submitting this particular form
                if($formconfig['debugmode']==true) {
                    $error .= "we're not submitting this form: ". $formname;
                }
                $sent = false;
            }
            else {
                // ok we're really submitting this form
                $isRecaptchaValid = true; // to prevent ReCaptcha check if not enabled

                if($this->config['recaptcha_enabled']){
                    $isRecaptchaValid = false; // by Default

                    $resp = recaptcha_check_answer($this->config['recaptcha_private_key'],
                        $this->getRemoteAddress(),
                        $_POST["recaptcha_challenge_field"],
                        $_POST["recaptcha_response_field"]);

                    $isRecaptchaValid = $resp->is_valid;
                }

                if($isRecaptchaValid) {
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
                        }
                        else {
                            $error = $formconfig['message_technical'];
                        }
                    }
                    else {
                        $error = $formconfig['message_error'];
                    }
                }
                else {
                    $error = $this->config['recaptcha_error_message'];
                }
            }
        }

        $formhtml = $this->app['render']->render($formconfig['template'], array(
            "submit" => "Send",
            "form" => $form->createView(),
            "message" => $message,
            "error" => $error,
            "sent" => $sent,
            "formname" => $formname,
            "recaptcha_html" => ($this->config['recaptcha_enabled'] ? recaptcha_get_html($this->config['recaptcha_public_key']) : ''),
            "recaptcha_theme" => ($this->config['recaptcha_enabled'] ? $this->config['recaptcha_theme'] : ''),
            "button_text" => $formconfig['button_text']
        ));

        return new \Twig_Markup($formhtml, 'UTF-8');
    }

    private function processForm($formconfig, $form, $formname)
    {
        if(!$this->app['request']->request->has($formname)) {
            // we're not submitting this particular form
            if($formconfig['debugmode']==true) {
                \Dumper::dump("we're not submitting this form: ". $formname);
            }
            return;
        }

        $data = $form->getData();

        if($formconfig['debugmode']==true) {
            \Dumper::dump('Processing '.$formname);
            \Dumper::dump($formconfig);
            // This yields a Fatal Error: "FormBuilder methods cannot be accessed anymore once the builder is turned into a FormConfigInterface instance."
            //\Dumper::dump($form);
            \Dumper::dump($data);
            \Dumper::dump($this->app['request']->files);
        }

        // $data contains the posted data. For legibility, change boolean fields to "yes" or "no".
        foreach($data as $key => $value) {
            // For legibility, change boolean fields to "yes" or "no".
            if (gettype($value)=="boolean") {
                $data[$key] = ($value ? "yes" : "no");
            }

            if (!empty($formconfig['fields'][$key]['role'])) {
                $role = $formconfig['fields'][$key]['role'];

                switch($role) {
                    case 'sequence':
                        $data[$key] = $this->getSequence($formconfig, $form, $formname, $key);
                        break;
                    default:
                        break;
                }
            }

            // Save the choice label, not the submitted safe string value.
            if ($formconfig['fields'][$key]['type'] == 'choice' && !empty($formconfig['fields'][$key]['choices'])) {
                $field = $formconfig['fields'][$key];
                $options = $field['choices'];

                if(isset($field['use_as']) && in_array($field['use_as'], array('from_email', 'to_email', 'cc_email', 'bcc_email'))) {
                    // do nothing because this field is be an email field
                    // $data[$key] = array($options[$value] => $value);
                    $tmp_email = $value;
                    $tmp_name = ($options[$value]!=$value)?$options[$value]:$value;

                    switch($field['use_as']) {
                        case 'from_email':
                            // set the special sender for this form
                            // add the values to the formconfig in case we want to see this later
                            $formconfig['from_email'] = $tmp_email;
                            $formconfig['from_name'] = $tmp_name;
                            // \Dumper::dump('Overriding from_email for '.$formname . ' with '. $tmp_name . ' <'. $tmp_email.'>');
                            break;
                        case 'to_email':
                            // add another recipient
                            // add the values to the formconfig in case we want to see this later
                            $formconfig['recipient_email'] = $tmp_email;
                            $formconfig['recipient_name'] = $tmp_name;
                            // \Dumper::dump('Overriding recipient_email for '.$formname . ' with '. $tmp_name . ' <'. $tmp_email.'>');
                            break;
                        case 'cc_email':
                            // add another carbon copy recipient
                            $formconfig['recipient_cc_email'] = $tmp_email;
                            $formconfig['recipient_cc_name'] = $tmp_name;
                            // \Dumper::dump('Overriding recipient_cc_email for '.$formname . ' with '. $tmp_name . ' <'. $tmp_email.'>');
                            break;
                        case 'bcc_email':
                            // add another blind carbon copy recipient
                            $formconfig['recipient_bcc_email'] = $tmp_email;
                            $formconfig['recipient_bcc_name'] = $tmp_name;
                            // \Dumper::dump('Overriding recipient_bcc_email for '.$formname . ' with '. $tmp_name . ' <'. $tmp_email.'>');
                            break;
                    }
                } elseif(is_array($value)) {
                    // replace keys with values for display in the email
                    foreach($value as $k => $v) {
                        if($options[$v] != $v) {
                            $data[$key][$k] = $options[$v];
                        }
                    }
                } elseif(isset($options[$value]) && $options[$value] != $value) {
                    $data[$key] = $options[$value];
                }

            }
        }

        if($formconfig['debugmode']==true) {
            \Dumper::dump('Prepared data for '.$formname);
            \Dumper::dump($data);
        }

        $fileSystem = new Filesystem;

        // Some fieldtypes (like 'date' and 'file') require post-processing.
        foreach ($formconfig['fields'] as $fieldname => $fieldvalues) {

            // Check if we have fields of type 'file'. If so, fetch them, and move them
            // to the designated folder.
            if ($fieldvalues['type'] == "file") {
                if (empty($formconfig['storage_location']) && $formconfig['attach_files']===false) {
                    die("You must set the storage_location in the field $fieldname if you do not use attachments.");
                }
                elseif(empty($formconfig['storage_location']) && $formconfig['attach_files']==false) {
                    // temporary files location will be a subdirectory of the cache
                    $path = BOLT_CACHE_DIR;
                    $linkpath = $this->app['paths']['app'] . 'cache';
                }
                else {
                    // files location will be a subdirectory of the files
                    $path = $this->app['paths']['filespath'] . "/" . $formconfig['storage_location'];
                    $linkpath = $this->app['paths']['files'] . $formconfig['storage_location'];
                }

                // make sure the path is exists
                if (!is_dir($path)) {
                    $fileSystem->mkdir($path);
                }

                if (!is_writable($path)) {
                    die("The path $path is not writable.");
                }

                $files = $this->app['request']->files->get($form->getName());
                if(array_key_exists($fieldname, $files) && !empty($files[$fieldname])) {
                    $originalname = strtolower($files[$fieldname]->getClientOriginalName());
                    $filename = sprintf(
                        "%s-%s-%s.%s",
                        date('Y-m-d'),
                        str_replace('upload', '', $fieldname),
                        $this->app['randomgenerator']->generateString(12, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890'),
                        getExtension($originalname)
                    );
                    $link = sprintf("%s%s/%s", $this->app['paths']['hosturl'], $linkpath, $filename);

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
                    }
                    else {
                        $data[$fieldname] = "Invalid upload, ignored ($originalname)";
                    }
                }
            }

            // Fields of type 'date' are \DateTime objects. Convert them to string, for sending in emails, etc.
            if (($fieldvalues['type'] == "date") && ($data[$fieldname] instanceof \DateTime)) {
                $format = isset($fieldvalues['format']) ? $fieldvalues['format'] : "Y-m-d";
                $data[$fieldname] = $data[$fieldname]->format($format);
            }
            if ($fieldvalues['type'] == "ip") {
                $data[$fieldname] = $this->getRemoteAddress();
            }
            if ($fieldvalues['type'] == "remotehost") {
                $data[$fieldname] = $this->getRemoteHost();
            }
            if ($fieldvalues['type'] == "useragent") {
                $data[$fieldname] = $this->getRemoteAgent();
            }
            if ($fieldvalues['type'] == "timestamp") {
                $format = "%F %T";
                $data[$fieldname] = strftime($format);
            }
            if ($fieldvalues['type'] == "choice" && $fieldvalues['multiple'] == true) {
                // just to be sure
                if (is_array( $data[$fieldname])) {
                    $data[$fieldname] = implode(', ', $data[$fieldname]); // maybe <li> items in <ul>
                }
            }

        }

        if($formconfig['debugmode']==true) {
            \Dumper::dump('Prepared files for '.$formname);
            \Dumper::dump($data);
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

        $mailhtml = $this->app['render']->render($formconfig['mail_template'], array(
            'form' =>  $data,
            'config' => $formconfig));

        if($formconfig['debugmode']==true) {
            \Dumper::dump('Mail html for '.$formname);
            \Dumper::dump($mailhtml);
        }

        if (!empty($formconfig['mail_subject'])) {
            $subject = $formconfig['mail_subject'];
        }
        else {
            $subject = '[SimpleForms] ' . $formname;
        }

        if (empty($formconfig['from_email'])) {
            $formconfig['from_email'] = $formconfig['recipient_email'];
        }

        if (empty($formconfig['from_name'])) {
            $formconfig['from_name'] = $formconfig['recipient_name'];
        }

        // Compile the message..
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html');

        // set the default recipient for this form
        if (!empty($formconfig['recipient_email'])) {
            $message->setTo(array($formconfig['recipient_email'] => $formconfig['recipient_name']));
            $this->app['log']->add('Set Recipient for '. $formname . ' to '. $formconfig['recipient_email'], 3);
        }

        // set the default sender for this form
        if (!empty($formconfig['from_email'])) {
            $message->setFrom(array($formconfig['from_email'] => $formconfig['from_name']));
            $this->app['log']->add('Set Sender for '. $formname . ' to '. $formconfig['from_email'], 3);
        }

        // add attachments if enabled in config
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
        }
        else {
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
                if (in_array($values['use_as'], array('to_email', 'from_email', 'cc_email', 'bcc_email'))) {
                    $tmp_email = false;

                    if($values['type']=="email") {
                        $tmp_email = $data[$key];

                        if(isset($values['use_with'])) {
                            $tmp_name = $data[$values['use_with']];
                            if(!$tmp_name) {
                                $tmp_name = $tmp_email;
                            } else {
                                $formconfig['recipient_name'] = $tmp_name;
                            }
                        }
                        else {
                            $tmp_name = $tmp_email;
                        }
                    }
                    elseif($values['type']=="choice") {
                        $tmp_email = $data[$key];
                        if(array_key_exists($tmp_email, $formconfig['fields'][$key])) {
                            $tmp_name = $formconfig['fields'][$key];
                        }
                        else {
                            $tmp_name = $tmp_email;
                        }
                    }

                    if($tmp_email) {
                        switch($values['use_as']) {
                            case 'from_email':
                                // set the special sender for this form
                                $message->setFrom(array($tmp_email => $tmp_name));
                                // add the values to the formconfig in case we want to see this later
                                if (empty($formconfig['from_email'])) {
                                    $formconfig['from_email'] = $tmp_email;
                                    if(!isset($formconfig['from_name'])) {
                                        $formconfig['from_name'] = $tmp_name;
                                    }
                                }
                                break;
                            case 'to_email':
                                // check if recipient name is something useful
                                // if it is already set somewhere use that
                                if(!isset($formconfig['recipient_name'])) {
                                    $formconfig['recipient_name'] = $tmp_name;
                                } else {
                                    $tmp_name = $formconfig['recipient_name'];
                                }
                                // add another recipient

                                $message->addTo($tmp_email, $tmp_name);
                                // add the values to the formconfig in case we want to see this later
                                if (empty($formconfig['recipient_email'])) {
                                    $formconfig['recipient_email'] = $tmp_email;
                                }
                                break;
                            case 'cc_email':
                                // add another carbon copy recipient
                                $message->addCc($tmp_email, $tmp_name);
                                break;
                            case 'bcc_email':
                                // add another blind carbon copy recipient
                                $message->addBcc($tmp_email, $tmp_name);
                                break;
                        }
                    }
                }
            }
        }

        // log the attempt
        $this->app['log']->add('Sending message '. $formname
                               . ' from '. $formconfig['from_email']
                               . ' to '. $formconfig['recipient_email'], 3);

        $res = $this->app['mailer']->send($message);

        // log the result of the attempt
        if ($res) {
            if($formconfig['testmode']) {
                $this->app['log']->add('Sent email from ' . $formname . ' to '. $formconfig['testmode_recipient'] . ' (in testmode) - ' . $formconfig['recipient_name'], 3);
            }
            else {
                $this->app['log']->add('Sent email from ' . $formname . ' to '. $formconfig['recipient_email'] . ' - ' . $formconfig['recipient_name'], 3);
            }
        }

        return $res;

    }

    /**
     * Get the user's IP-address for logging, even if they're behind a non-trusted proxy.
     * Note: these addresses can't be 'trusted', Use them for logging only.
     *
     * @return string
     */
    private function getRemoteAddress()
    {
        $server = $this->app['request']->server;
        if ($server->has('HTTP_CLIENT_IP')) {
            return $server->get('HTTP_CLIENT_IP');
        }
        if ($server->has('HTTP_X_FORWARDED_FOR')) {
            return $server->get('HTTP_X_FORWARDED_FOR');
        }
        return $server->get('REMOTE_ADDR');
    }


    /**
     * Get the user's remote hostname for logging.
     * Ignore proxy stuff
     * Note: these addresses can't be 'trusted', Use them for logging only.
     *
     * @return string
     */
    private function getRemoteHost()
    {
        $server = $this->app['request']->server;

        $host = $server->get('REMOTE_HOST');
        if($host) {
            return $host;
        } else {
            return '';
        }
    }

    /**
     * Get the user's user agent string for logging
     * Note: these addresses can't be 'trusted', Use them for logging only.
     *
     * @return string
     */
    private function getRemoteAgent()
    {
        $server = $this->app['request']->server;
        return $server->get('HTTP_USER_AGENT');
    }

    /**
     * Get the next number from a sequence
     *
     * @return int
     */
    private function getSequence($formconfig, $form, $formname, $column)
    {
        $sequence = null;

        // Attempt get the next sequence from a table, if specified..
        if (!empty($formconfig['insert_into_table'])) {
            try {

                $query = sprintf(
                    "SELECT MAX(%s) as max FROM %s",
                    $column,
                    $formconfig['insert_into_table']
                );
                $sequence = $this->app['db']->executeQuery( $query )->fetchColumn();

            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
                $this->app['log']->add("SimpleForms could not fetch next sequence number from table". $formconfig['insert_into_table'] . ' - check if the table exists.', 3);
                echo "Couldn't fetch next sequence number from table " . $formconfig['insert_into_table'] . ".";
            }
        }

        $sequence++;

        if($formconfig['debugmode']==true) {
            \Dumper::dump('Get sequence for '.$formname . ' column: '. $column . ' - '. $sequence);
        }

        return $sequence;
    }

}
