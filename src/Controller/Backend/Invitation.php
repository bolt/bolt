<?php

namespace Bolt\Controller\Backend;

use Bolt\Form\FormType\InviteCreateType;
use Bolt\Form\FormType\InviteShareType;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Backend controller for invitation code generation.
 *
 * @author Carlos Perez <mrcarlosdev@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Invitation extends BackendBase
{
    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/users/invite', 'inviteCreate')
            ->bind('inviteCreate');

        $c->match('/users/invite/share/{code}', 'inviteShare')
            ->assert('code', '.*')
            ->bind('inviteShare');

        $c->before([$this, 'before']);
    }

    /**
     * {@inheritdoc}
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        return parent::before($request, $app, 'useredit:invitation');
    }

    /**
     * Share link route.
     *
     * @param Request $request The Symfony Request
     * @param string  $code    The invitation code
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function inviteShare(Request $request, $code)
    {
        // Generate the full URL to put it into the link field
        $linkUrl = $this->generateUrl('invitation', ['code' => $code], UrlGeneratorInterface::ABSOLUTE_URL);
        $entity = (object) ['to' => null, 'subject' => null, 'message' => null, 'link' => $linkUrl];
        $form = $this->createFormBuilder(InviteShareType::class, $entity)
            ->getForm()
            ->handleRequest($request)
        ;

        // Check if the form was POST-ed, and valid. If so, store the invitation.
        if ($form->isValid()) {
            $this->sendInvite($entity);

            return new RedirectResponse($this->generateUrl('users'));
        }

        // Preparing the forms for the view
        $context = [
            'form' => $form->createView(),
            'code' => $code,
            'link' => $linkUrl,
        ];

        return $this->render('@bolt/invitation/share.twig', $context);
    }

    /**
     * Send the invitation to the specified address.
     *
     * @param object $entity
     */
    private function sendInvite($entity)
    {
        $logger = $this->app['logger.system'];
        $mailer = $this->app['mailer'];
        $twig = $this->app['twig'];
        $spool = $this->app['swiftmailer.spooltransport']->getSpool();
        $transport = $this->app['swiftmailer.transport'];
        $from  = $this->getOption('general/mailoptions/senderMail', $this->getUser()->getEmail());

        // Compile the email with the invitation link.
        $mailHtml = $twig->render('@bolt/mail/invitation.twig', [
            'message' => $entity->message,
            'link'    => $entity->link,
        ]);

        $message = $mailer
            ->createMessage('message')
            ->setSubject($entity->subject)
            ->setFrom($from)
            ->setReplyTo($from)
            ->setTo($entity->to)
            ->setBody(strip_tags($mailHtml))
            ->addPart($mailHtml, 'text/html')
        ;

        $failed = true;
        $failedRecipients = [];

        try {
            // Try and send immediately
            $recipients = $mailer->send($message, $failedRecipients);
            $spool->flushQueue($transport);
            if ($recipients) {
                $logger->info(sprintf('Invitation request sent to %s .', $entity->to), ['event' => 'authentication']);
                $failed = false;
            }
        } catch (\Exception $e) {
            // Notify below
        }

        if ($failed) {
            $logger->error(sprintf('Failed to send invitation request sent to %s', $entity->to), ['event' => 'authentication']);
            $this->flashes()->error(Trans::__('page.invitation.share.email-error'));
        } else {
            $this->flashes()->success(Trans::__('page.invitation.share.email-sent', ['%email%' => $entity->to]));
        }
    }

    /**
     * Invitation link route.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function inviteCreate(Request $request)
    {
        $options = [
            'data_class' => Entity\Invitation::class,
            'expiryMin'  => '30 minutes', // TODO make configurable
            'expiryMax'  => '48 hours',
        ];

        $form = $this->createFormBuilder(InviteCreateType::class, null, $options)
            ->getForm()
            ->handleRequest($request)
        ;

        if ($form->isValid()) {
            //Generate token for invitation code
            $random = $this->app['session.generator'];
            $token = $random->generateId();
            $owner = $this->getUser();

            $repo = $this->getRepository(Entity\Invitation::class);
            $inviteEntity = $form->getData();
            $inviteEntity->setToken($token);
            $inviteEntity->setOwnerid($owner);

            if ($repo->save($inviteEntity)) {
                $this->flashes()->success(Trans::__('page.invitation.message.code-saved', ['%code%' => $token]));
            } else {
                $this->flashes()->error(Trans::__('page.invitation.message.code-failed'));
            }

            return new RedirectResponse($this->generateUrl('inviteShare', ['code' => $token]));
        }

        // Preparing the forms for the view
        $context = [
            'form' => $form->createView(),
        ];

        return $this->render('@bolt/invitation/generate.twig', $context);
    }
}
