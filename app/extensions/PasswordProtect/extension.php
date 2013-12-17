<?php
// PasswordProtect Extension for Bolt

namespace PasswordProtect;

use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{

    public function info()
    {

        $data = array(
            'name' =>"PasswordProtect extension",
            'description' => "A small extension to password protect pages on your site, ".
                             "when using <code>{{ passwordprotect() }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "1.4",
            'type' => "Twig function",
            'first_releasedate' => "2013-12-11",
            'latest_releasedate' => "2013-12-11",
        );

        return $data;

    }

    public function initialize()
    {

        $this->addTwigFunction('passwordprotect', 'passwordProtect');
        $this->addTwigFunction('passwordform', 'passwordForm');

    }

    /**
     * Check if we're currently allowed to view the page. If not, redirect to
     * the password page.
     *
     * @return \Twig_Markup
     */
    public function passwordProtect()
    {

        if ($this->app['session']->get('passwordprotect') == 1) {
            return new \Twig_Markup("<!-- Password protection OK! -->", 'UTF-8');
        } else {

            $redirectto = $this->app['storage']->getContent($this->config['redirect'], array('returnsingle' => true));
            $returnto = $this->app['request']->getRequestUri();
            simpleredirect($redirectto->link(). "?returnto=" . urlencode($returnto));

        }


    }


    /**
     * Show the password form. If the visitor gives the correct password, they
     * are redirected to the page they came from, if any.
     *
     * @return \Twig_Markup
     */
    public function passwordForm()
    {

        // Set up the form.
        $form = $this->app['form.factory']->createBuilder('form', $data)
            ->add('password', 'password')
            ->getForm();


        if ($this->app['request']->getMethod() == 'POST') {

            $form->bind($this->app['request']);

            $data = $form->getData();

            if ($form->isValid() && ($data['password'] == $this->config['password'])) {

                // Set the session var, so we're authenticated..
                $this->app['session']->set('passwordprotect', 1);

                // Print a friendly message..
                printf("<p class='message-correct'>%s</p>", $this->config['message_correct']);

                $returnto = $this->app['request']->get('returnto');

                // And back we go, to the page we originally came from..
                if (!empty($returnto)) {
                    simpleredirect($returnto);
                }

            } else {

                // Remove the session var, so we can test 'logging off'..
                $this->app['session']->set('passwordprotect', 0);

                // Print a friendly message..
                printf("<p class='message-wrong'>%s</p>", $this->config['message_wrong']);
            }

        }

        // Render the form, and show it it the visitor.
        $this->app['twig.loader.filesystem']->addPath(__DIR__);
        $html = $this->app['twig']->render('assets/passwordform.twig', array('form' => $form->createView()));

        return new \Twig_Markup($html, 'UTF-8');

    }


}
