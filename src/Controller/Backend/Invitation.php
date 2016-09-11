<?php
namespace Bolt\Controller\Backend;

use Bolt\Storage\Entity;
use Bolt\Storage\Entity\Invitations;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Bolt\Session\Generator\RandomGenerator;

/**
 * Backend controller for invitation code generation.
 *
 * @author Carlos Perez <mrcarlosdev@gmail.com>
 */
class Invitation extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/users/invite', 'invitationLink')
            ->bind('invitationlink');

        $c->match('/users/invite/share/{code}', 'shareLink')
            ->assert('code', '.*')
            ->bind('shareLink');

        $c->match('/users/invite/generate', 'generateLink')
            ->bind('generatelink');

        $c->match('/users/invite/email', 'sendLink')
            ->bind('sendlink');

    }

    /**
     * Share link route.
     *
     * @param Request $request    The Symfony Request
     * @param string  $code       The invitation code
     * @param bool    $send       Send the email if it is possible
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function shareLink(Request $request, $code, $send = true)
    {
        //get the full url to put it into the code field
        $fullcode = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath().'/bolt/invitation/'.$code;

        // Get and generate the base form to share the invitation code by email
        $formEmailView = $this->getInvitationEmailForm($fullcode);

        $formEmailView = $this->setShareFormValidation($formEmailView);

        $formEmailView = $formEmailView->getForm();

        // Check if the form was POST-ed, and valid. If so, store the invitation.
        if ($request->isMethod('POST') && $send) {
            $formEmailView->submit($request->get($formEmailView->getName()));

            if ($formEmailView->isValid()) {
                $to = $formEmailView['to']->getData();
                $subject = $formEmailView['subject']->getData();
                $message = $formEmailView['message']->getData();

                // Get the current user to use his email on the "From" field.
                $userEntity = new Entity\Users($this->getUser());

                $from = $this->app['config']->get('general/mailoptions/senderMail', $userEntity->getEmail());

                // Compile the email with the invitation link.
                $mailhtml = $this->app['render']->render(
                    '@bolt/mail/invitation.twig',
                    [
                        'message' => $message,
                        'link' => $fullcode,
                    ]
                );

                $message = $this->app['mailer']
                    ->createMessage('message')
                    ->setSubject($subject)
                    ->setFrom($from)
                    ->setReplyTo($from)
                    ->setTo($to)
                    ->setBody(strip_tags($mailhtml))
                    ->addPart($mailhtml, 'text/html');

                $failed = true;
                $failedRecipients = [];

                try {
                    $recipients = $this->app['mailer']->send($message, $failedRecipients);

                    // Try and send immediately
                    $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);

                    if ($recipients) {
                        $this->app['logger.system']->info("Invitation request sent to '" . $to . "'.", ['event' => 'authentication']);
                        $failed = false;
                    }
                } catch (\Exception $e) {
                    // Notify below
                }

                if ($failed) {
                    $this->app['logger.system']->error("Failed to send invitation request sent to '" . $to . "'.", ['event' => 'authentication']);

                    $this->flashes()->error(Trans::__('page.invitation.share-options.error-email'));
                } else {
                    $this->flashes()->success(Trans::__('page.invitation.share-options.email-sent', ['%email%' => $to]));
                }

                // Preparing the forms for the view
                $context = [
                    'form' => $formEmailView->createView(),
                    'code' => $code,
                ];

                return $this->render('@bolt/invitation/share.twig', $context);
            }
        }

        // Preparing the forms for the view
        $context = [
            'form' => $formEmailView->createView(),
            'code' => $code,
        ];

        return $this->render('@bolt/invitation/share.twig', $context);
    }

    /**
     * Invitation link route.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invitationLink(Request $request)
    {
        $invitation = new Entity\Invitations();

        // Get and generate the base form to generate the invitation code
        $formCodeView = $this->getGenerateInvitationForm($invitation);

        $formCodeView = $this->setInvitationFormValidation($formCodeView);

        $formCodeView = $formCodeView->getForm();

        // Check if the form was POST-ed, and valid. If so, store the invitation.
        if ($request->isMethod('POST')) {
            $formCodeView->submit($request->get($formCodeView->getName()));


            if ($formCodeView->isValid()) {
                //Generate token for invitation code
                $random = new RandomGenerator($this->app['randomgenerator'], $this->app['session.generator.bytes_length']);
                $code = $random->generateId();

                $invitationEntity = new Entity\Invitations();

                $expiration = $formCodeView['expiration']->getData();
                $roles = $formCodeView['roles']->getData();

                $invitationEntity->setToken($code);
                $invitationEntity->setRoles($roles);
                $invitationEntity->setExpiration($expiration);

                $this->getRepository('Bolt\Storage\Entity\Invitations')->save($invitationEntity);

                if ($this->getRepository('Bolt\Storage\Entity\Invitations')->save($invitationEntity)) {
                    $this->flashes()->success(Trans::__('page.invitation.message.code-saved', ['%code%' => $code]));
                } else {
                    $this->flashes()->error(Trans::__('page.invitation.message.saving-code', ['%code%' => $code]));
                }

                //return $this->app->redirect($this->app["url_generator"]->generate('shareLink', $code));
                //return new RedirectResponse($this->generateUrl('shareLink', $code));
                return $this->shareLink($request, $code, false);
            }
        }

        // Get the current user to know what user role they can invite
        $currentUser = $this->getUser();

        $manipulatableRoles = $this->app['permissions']->getManipulatableRoles($currentUser->toArray());
        foreach ($formCodeView['roles'] as $role) {
            if (!in_array($role->vars['value'], $manipulatableRoles)) {
                $role->vars['attr']['disabled'] = 'disabled';
            }
        }

        // Preparing the forms for the view
        $context = [
            'form' => $formCodeView->createView(),
        ];

        return $this->render('@bolt/invitation/generate.twig', $context);
    }

    /**
     * Validate the generate invitation form.
     *
     * Use a custom validator to check:
     *   * Expiration date and time are not expired
     *
     * @param FormBuilder $form
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function setInvitationFormValidation(FormBuilder $form)
    {
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $expiration = $form['expiration']->getData();
                $roles = $form['roles']->getData();

                //check if expiration date is not in the past
                if (new \DateTime() > $expiration) {
                    $error = new FormError(Trans::__('page.edit-users.error.datetime-expired'));
                    $form['expiration']->addError($error);
                }
            }
        );


        return $form;
    }

    /**
     * Validate the share invitation form.
     *
     * Use a custom validator to check:
     *   * Email is a valid address
     *
     * @param FormBuilder $form
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function setShareFormValidation(FormBuilder $form)
    {
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $to = $form['to']->getData();
                $subject = $form['subject']->getData();
                $text = $form['message']->getData();


                // Validate the form to send the invitation code by email
                $book = array(
                    'to' => $to,
                    'subject' => $subject,
                    'text' => $text,
                );

                $constraint = new Assert\Collection(array(
                    'to' => new Assert\Email(),
                    'subject' => new Assert\NotBlank(),
                    'text' => new Assert\NotBlank(),
                ));

                $violationList = $this->app['validator']->validate($book, $constraint);

                if (count($violationList) > 0) {
                    foreach ($violationList as $violation) {
                        new FormError($violation->getMessage());
                    }
                }
            }
        );


        return $form;
    }

    /**
     * Create a form to generate an invitation code with the form builder.
     *
     * @param Entity\Invitations $invitation
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getGenerateInvitationForm(Entity\Invitations $invitation)
    {

        // Start building the form
        $form = $this->createFormBuilder(FormType::class, $invitation);

        // Get the roles
        $roles = array_map(
            function ($role) {
                return $role['label'];
            },
            $this->app['permissions']->getDefinedRoles()
        );

        $form
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'choices' => $roles,
                    'expanded' => true,
                    'multiple' => true,
                    'label' => Trans::__('page.invitation.label.assigned-roles'),
                ]
            )->add('expiration', DateTimeType::class, array(
                'input' => 'datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => true,
                'disabled' => false,
                'data' => new \DateTime("+1 week"),
                'label' => Trans::__('page.invitation.expiration-date'),

            ));

        return $form;
    }

    /**
     * Create a form to send the invitation code by email with the form builder.
     *
     * @param string $code    Invitation code to be sent by email
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getInvitationEmailForm($code)
    {
        $defaults = array(
            'invitationLink' => $code,
        );

        // Start building the form
        $form = $this->createFormBuilder(FormType::class, $defaults);

        $form
            ->add(
                'invitationLink',
                TextType::class,
                [
                    'label' => Trans::__('page.invitation.share-options.copy'),
                    'disabled' => true,
                ]
            )->add(
                'copy',
                ButtonType::class,
                [
                    'label' => Trans::__('page.invitation.button.copy'),
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ]
            )->add(
                'to',
                TextType::class,
                [
                    'constraints' => new Assert\Email(),
                    'label' => Trans::__('page.invitation.share-options.to-email'),
                    'attr' => [
                        'placeholder' => Trans::__('page.invitation.share-options.to-placeholder'),
                        'class' => 'to',
                    ],
                ]
            )
            ->add(
                'subject',
                TextType::class,
                [
                    'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 32])],
                    'label' => Trans::__('page.invitation.share-options.subject-email'),
                    'attr' => [
                        'placeholder' => Trans::__('page.invitation.share-options.subject-placeholder'),
                        'class' => 'subject',
                    ],
                ]
            )
            ->add(
                'message',
                TextareaType::class,
                [
                    'constraints' => [new Assert\NotBlank()],
                    'label' => Trans::__('page.invitation.share-options.message-email'),
                    'attr' => [
                        'placeholder' => Trans::__('page.invitation.share-options.message-placeholder'),
                        'class' => 'message',
                    ],
                ]
            );

        return $form;
    }
}
