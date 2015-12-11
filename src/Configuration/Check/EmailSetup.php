<?php
namespace Bolt\Configuration\Check;

/**
 * Checks for email configuration.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class EmailSetup extends BaseCheck implements ConfigurationCheckInterface
{
    /** @var array */
    protected $options = [
        'type' => 'test',
        'host' => 'localhost',
        'ip'   => '127.0.0.1',
    ];

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        // Turn off the spool as we're only sending one message, and we don't
        // want to flush via the event listener as that fires on
        // KernelEvents::TERMINATE and we are unable to trap the error.
        $this->app['swiftmailer.use_spool'] = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function runCheck()
    {
        if ($this->checkMailOptions()) {
            return $this->results;
        }

        if (empty($this->options['host']) || $this->options['host'] === 'localhost') {
            $this->options['host'] = gethostname();
        }

        // Create an email
        $mailhtml = $this->getEmailHtml();

        $senderMail = $this->app['config']->get('general/mailoptions/senderMail', 'bolt@' . $this->options['host']);
        $senderName = $this->app['config']->get('general/mailoptions/senderName', $this->app['config']->get('general/sitename'));

        $this->sendMessage($senderMail, $senderName, $mailhtml);

        return $this->results;
    }

    protected function checkMailOptions()
    {
        $fail = false;
        if ($this->app['config']->get('general/mailoptions') === null) {
            $this->createResult()->fail()->setMessage("The 'mailoptions' parameters are not set in config.yml");

            return true;
        }

        if ($this->app['config']->get('general/mailoptions/transport') === null) {
            $this->createResult()->fail()->setMessage("The mailoptions 'transport' parameter is not set in config.yml");
            $fail = true;
        }

        if ($this->app['config']->get('general/mailoptions/spool') === null) {
            $this->createResult()->fail()->setMessage("The mailoptions 'spool' parameter is not set in config.yml");
            $fail = true;
        }

        return $fail;
    }

    /**
     * Render HTML for the sample email.
     *
     * @return string
     */
    private function getEmailHtml()
    {
        return $this->app['render']->render(
            'email/pingtest.twig',
            [
                'sitename' => $this->app['config']->get('general/sitename'),
                'user'     => $this->options['user']['displayname'],
                'ip'       => $this->options['ip'],
            ]
        )->getContent();
    }

    /**
     * Attempt to send the email message.
     *
     * @param string $senderMail
     * @param string $senderName
     * @param string $mailhtml
     */
    private function sendMessage($senderMail, $senderName, $mailhtml)
    {
        try {
            $message = $this->app['mailer']
                ->createMessage('message')
                ->setSubject('Test email from ' . $this->app['config']->get('general/sitename'))
                ->setFrom([$senderMail                   => $senderName])
                ->setReplyTo([$senderMail                => $senderName])
                ->setTo([$this->options['user']['email'] => $this->options['user']['displayname']])
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html')
            ;

            $this->app['swiftmailer.use_spool'] = false;
            if ($this->app['mailer']->send($message) > 0) {
                $this->createResult()->pass()->setMessage("Message sent to '" . $this->options['user']['email'] . "' from '" . $senderMail . "'.");
            } else {
                $this->createResult()->fail()->setMessage('No messages were able to be sent. Check your configuration.');
            }
        } catch (\Swift_TransportException $e) {
            $this->createResult()->fail()->setMessage('Swiftmailer exception')->setException($e);
        } catch (\Exception $e) {
            $this->createResult()->fail()->setMessage('PHP exception')->setException($e);
        }
    }
}
