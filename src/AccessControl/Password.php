<?php

namespace Bolt\AccessControl;

use Bolt\Events\AccessControlEvents;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository\UsersRepository;
use Bolt\Translation\Translator as Trans;
use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Password handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Password
{
    /** @var EntityManager */
    protected $em;
    /** @var \Silex\Application $app */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->em = $this->app['storage'];
    }

    /**
     * Set a random password for user.
     *
     * @param string $username user specified by ID, username or email address
     *
     * @return string|bool new password or FALSE when no match for username
     */
    public function setRandomPassword($username)
    {
        $password = false;

        /** @var UsersRepository $repo */
        $repo = $this->em->getRepository(Entity\Users::class);
        if ($userEntity = $repo->getUser($username)) {
            $password = $this->app['randomgenerator']->generateString(12);

            $userEntity->setPassword($password);
            $userEntity->setShadowpassword(null);
            $userEntity->setShadowtoken(null);
            $userEntity->setShadowvalidity(null);

            $this->em->getRepository(Entity\Users::class)->save($userEntity);

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
     * @param Event  $event
     *
     * @return bool
     */
    public function resetPasswordConfirm($token, $remoteIP, Event $event)
    {
        // Hash the remote caller's IP with the token
        $tokenHash = md5($token . '-' . str_replace('.', '-', $remoteIP));

        /** @var UsersRepository $repo */
        $repo = $this->em->getRepository(Entity\Users::class);
        if ($userEntity = $repo->getUserShadowAuth($tokenHash)) {
            $userAuth = $repo->getUserAuthData($userEntity->getId());
            // Update entries
            $userEntity->setPassword($userAuth->getShadowpassword());
            $userEntity->setShadowpassword(null);
            $userEntity->setShadowtoken(null);
            $userEntity->setShadowvalidity(null);

            $this->em->getRepository(Entity\Users::class)->save($userEntity);

            $this->app['logger.flash']->clear();
            $this->app['logger.flash']->success(Trans::__('general.access-control.reset-successful'));
            $this->app['dispatcher']->dispatch(AccessControlEvents::RESET_SUCCESS, $event);

            return true;
        }
        // That was not a valid token, or too late, or not from the correct IP.
        $this->app['logger.system']->error('Somebody tried to reset a password with an invalid token.', ['event' => 'authentication']);
        $this->app['logger.flash']->error(Trans::__('general.access-control.reset-failed'));
        $this->app['dispatcher']->dispatch(AccessControlEvents::RESET_FAILURE, $event);

        return false;
    }

    /**
     * Sends email with password request. Accepts email or username.
     *
     * @param string $username
     * @param string $remoteIP
     * @param Event  $event
     *
     * @return bool
     */
    public function resetPasswordRequest($username, $remoteIP, Event $event)
    {
        /** @var UsersRepository $repo */
        $repo = $this->em->getRepository(Entity\Users::class);
        /** @var Entity\Users $userEntity */
        $userEntity = $repo->getUser($username);

        if (!$userEntity) {
            // For safety, this is the message we display, regardless of whether user exists.
            $this->app['logger.flash']->clear();
            $this->app['logger.flash']->info(Trans::__('page.login.password-reset-link-sent', ['%user%' => $username]));
            $this->app['dispatcher']->dispatch(AccessControlEvents::RESET_FAILURE, $event);

            return false;
        }

        // Generate shadow password and hash
        $shadowPassword = $this->app['randomgenerator']->generateString(12);
        $shadowPasswordHash = $this->app['password_factory']->createHash($shadowPassword, '$2y$');

        // Generate shadow token and hash
        $shadowToken = $this->app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', $remoteIP));

        // Set the shadow password and related stuff in the database.
        $userEntity->setShadowpassword($shadowPasswordHash);
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(2));

        $this->em->getRepository(Entity\Users::class)->save($userEntity);

        $mailoptions = $this->app['config']->get('general/mailoptions'); // PHP 5.4 compatibility
        if (empty($mailoptions)) {
            $this->app['logger.flash']->danger(Trans::__('general.phrase.error-mail-options-not-set'));
        }

        // Sent the password reset notification
        $this->resetPasswordNotification($userEntity, $shadowPassword, $shadowToken);
        $this->app['dispatcher']->dispatch(AccessControlEvents::RESET_REQUEST, $event);

        return true;
    }

    /**
     * Send the password reset link notification to the user.
     *
     * @param Entity\Users $userEntity
     * @param string       $shadowPassword
     * @param string       $shadowToken
     */
    private function resetPasswordNotification(Entity\Users $userEntity, $shadowPassword, $shadowToken)
    {
        $config = $this->app['config'];
        $flash = $this->app['logger.flash'];
        $mailer = $this->app['mailer'];
        $logger = $this->app['logger.system'];
        $twig = $this->app['twig'];
        $urlGenerator = $this->app['url_generator'];

        $shadowLink = $urlGenerator->generate(
            'resetpassword',
            ['token' => $shadowToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Compile the email with the shadow password and reset link.
        $mailHtml = $twig->render(
            '@bolt/mail/passwordreset.twig',
            [
                'user'           => $userEntity,
                'shadowpassword' => $shadowPassword,
                'shadowtoken'    => $shadowToken,
                'shadowvalidity' => Carbon::now()->addHours(2)->format('Y-m-d H:i:s'),
                'shadowlink'     => $shadowLink,
            ]
        );

        $subject = sprintf('[ Bolt / %s ] Password reset.', $config->get('general/sitename'));
        $name = $config->get('general/mailoptions/senderName', $config->get('general/sitename'));
        $email = $config->get('general/mailoptions/senderMail', $userEntity->getEmail());
        $from = [$email => $name];

        $message = $mailer
            ->createMessage('message')
            ->setSubject($subject)
            ->setFrom($from)
            ->setReplyTo($from)
            ->setTo([$userEntity->getEmail() => $userEntity->getDisplayname()])
            ->setBody(strip_tags($mailHtml))
            ->addPart($mailHtml, 'text/html')
        ;

        $failed = true;
        $failedRecipients = [];

        try {
            $recipients = $mailer->send($message, $failedRecipients);

            // Try and send immediately
            $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);

            if ($recipients) {
                $logger->info("Password request sent to '" . $userEntity->getDisplayname() . "'.", ['event' => 'authentication']);
                $failed = false;
            }
        } catch (\Exception $e) {
            // Notify below
        }

        if ($failed) {
            $logger->error("Failed to send password request sent to '" . $userEntity['displayname'] . "'.", ['event' => 'authentication']);
            $flash->error(Trans::__('general.phrase.error-send-password-request'));
        }
    }
}
