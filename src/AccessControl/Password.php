<?php
namespace Bolt\AccessControl;

use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Carbon\Carbon;
use Hautelook\Phpass\PasswordHash;
use Silex\Application;

/**
 * Password handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Password
{
    /** @var \Silex\Application $app */
    protected $app;

    public function __construct(Application $app)
    {
        /*
         * $this->app['config']
         * $this->app['logger.system']
         * $this->app['logger.flash']
         * $this->app['mailer']
         * $this->app['randomgenerator']
         * $this->app['resources']
         * $this->app['render']
         */
        $this->app = $app;
    }

    /**
     * Set a random password for user.
     *
     * @param string $username User specified by ID, username or email address.
     *
     * @return string|boolean New password or FALSE when no match for username.
     */
    public function setRandomPassword($username)
    {
        $password = false;

        if ($userEntity = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->getUser($username)) {
            $password = $this->app['randomgenerator']->generateString(12);

            $userEntity->setPassword($password);
            $userEntity->setShadowpassword('');
            $userEntity->setShadowtoken('');
            $userEntity->setShadowvalidity(null);

            $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->save($userEntity);

            $this->app['logger.system']->info(
                "Password for user '{$userEntity->getUsername()}' was reset via Nut.",
                ['event' => 'authentication']
            );
        }

        return $password;
    }

    /**
     * Handle a password reset confirmation.
     *
     * @param string $token
     * @param string $remoteIP
     *
     * @return boolean
     */
    public function resetPasswordConfirm($token, $remoteIP)
    {
        // Hash the remote caller's IP with the token
        $tokenHash = md5($token . '-' . str_replace('.', '-', $remoteIP));

        if ($userEntity = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->getUserShadowAuth($tokenHash)) {
            // Update entries
            $userEntity->setPassword($userEntity->getShadowpassword());
            $userEntity->setShadowpassword('');
            $userEntity->setShadowtoken('');
            $userEntity->setShadowvalidity(null);
            $userEntity->setShadowSave(true);

            $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->save($userEntity);

            $this->app['logger.flash']->success(Trans::__('Password reset successful! You can now log on with the password that was sent to you via email.'));

            return true;
        } else {
            // That was not a valid token, or too late, or not from the correct IP.
            $this->app['logger.system']->error('Somebody tried to reset a password with an invalid token.', ['event' => 'authentication']);
            $this->app['logger.flash']->error(Trans::__('Password reset not successful! Either the token was incorrect, or you were too late, or you tried to reset the password from a different IP-address.'));

            return false;
        }
    }

    /**
     * Sends email with password request. Accepts email or username.
     *
     * @param string $username
     * @param string $remoteIP
     *
     * @return boolean
     */
    public function resetPasswordRequest($username, $remoteIP)
    {
        $userEntity = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->getUser($username);

        if (!$userEntity) {
            // For safety, this is the message we display, regardless of whether user exists.
            $this->app['logger.flash']->info(Trans::__("A password reset link has been sent to '%user%'.", ['%user%' => $username]));

            return false;
        }

        // Generate shadow password and hash
        $hasher = new PasswordHash($this->app['access_control.hash.strength'], true);
        $shadowPassword = $this->app['randomgenerator']->generateString(12);
        $shadowPasswordHash = $hasher->HashPassword($shadowPassword);

        // Generate shadow token and hash
        $shadowToken = $this->app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', $remoteIP));

        // Set the shadow password and related stuff in the database.
        $userEntity->setShadowpassword($shadowPasswordHash);
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(2));

        $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->save($userEntity);

        $mailoptions = $this->app['config']->get('general/mailoptions'); // PHP 5.4 compatibility
        if (empty($mailoptions)) {
            $this->app['logger.flash']->error(Trans::__("The email configuration setting 'mailoptions' hasn't been set. Bolt may be unable to send password reset."));
        }

        // Sent the password reset notification
        $this->resetPasswordNotification($userEntity, $shadowPassword, $shadowToken);

        return true;
    }

    /**
     * Send the password reset link notification to the user.
     *
     * @param Entity\Users $userEntity
     * @param string       $shadowpassword
     * @param string       $shadowtoken
     */
    private function resetPasswordNotification(Entity\Users $userEntity, $shadowpassword, $shadowtoken)
    {
        $shadowlink = sprintf(
            '%s%sresetpassword?token=%s',
            $this->app['resources']->getUrl('hosturl'),
            $this->app['resources']->getUrl('bolt'),
            urlencode($shadowtoken)
        );

        // Compile the email with the shadow password and reset link.
        $mailhtml = $this->app['render']->render(
            '@bolt/mail/passwordreset.twig',
            [
                'user'           => $userEntity,
                'shadowpassword' => $shadowpassword,
                'shadowtoken'    => $shadowtoken,
                'shadowvalidity' => date('Y-m-d H:i:s', strtotime('+2 hours')),
                'shadowlink'     => $shadowlink
            ]
        );

        $subject = sprintf('[ Bolt / %s ] Password reset.', $this->app['config']->get('general/sitename'));
        $name = $this->app['config']->get('general/mailoptions/senderName', $this->app['config']->get('general/sitename'));
        $email = $this->app['config']->get('general/mailoptions/senderMail', $userEntity->getEmail());
        $from = [$email => $name];

        $message = $this->app['mailer']
            ->createMessage('message')
            ->setSubject($subject)
            ->setFrom($from)
            ->setReplyTo($from)
            ->setTo([$userEntity['email'] => $userEntity['displayname']])
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html')
        ;
        $failed = true;
        $failedRecipients = [];

        try {
            $recipients = $this->app['mailer']->send($message, $failedRecipients);

            // Try and send immediately
            $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);

            if ($recipients) {
                $this->app['logger.system']->info("Password request sent to '" . $userEntity->getDisplayname() . "'.", ['event' => 'authentication']);
                $failed = false;
            }
        } catch (\Exception $e) {
            // Notify below
        }

        if ($failed) {
            $this->app['logger.system']->error("Failed to send password request sent to '" . $userEntity['displayname'] . "'.", ['event' => 'authentication']);
            $this->app['logger.flash']->error(Trans::__("Failed to send password request. Please check the email settings."));
        }
    }
}
