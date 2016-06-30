<?php
namespace Bolt\Controller\Backend;

use Silex\Application;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Backend controller for invitation code generation.
 *
 * @author Carlos Perez <mrcarlosdev@gmail.com>
 */
class Invitation extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/invitationlink', 'invitationLink')
            ->bind('invitationlink');

        $c->match('/generatelink', 'generateLink')
            ->bind('generatelink');

        $c->match('/sendlink', 'sendLink')
            ->bind('sendlink');

    }

    /**
     * Invitation link route.
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invitationLink()
    {
        // Get and generate the base form to generate the invitation code
        $form = $this->getGenerateInvitationForm($this->getUser());
        $form = $form->getForm();

        /** @var \Symfony\Component\Form\FormView|\Symfony\Component\Form\FormView[] $formView */
        $formCodeView = $form->createView();

        // Get and generate the base form to share the invitation code by email
        $form = $this->getInvitationEmailForm($this->getUser());
        $form = $form->getForm();

        /** @var \Symfony\Component\Form\FormView|\Symfony\Component\Form\FormView[] $formView */
        $formEmailView = $form->createView();

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
            'form' => $formCodeView,
            'emailform' => $formEmailView,
        ];

        return $this->render('@bolt/invitation/generate.twig', $context);
    }

    /**
     * Generate invitation code link.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function generateLink(Request $request)
    {
        // Get the Roles for the invitation code
        $roles = array();

        if (null !== ($request->request->get('roles'))) {
            $roles = $request->request->get('roles');
        }

        // Get the expiration date for the invitation code
        $expiration_date = $request->request->get('expiration_date');
        $expiration_time = $request->request->get('expiration_time');

        //validate expiration date (not empty, not in the past)
        $expiration = new \DateTime(str_replace("/", "-", $expiration_date . " " . $expiration_time));
        $expiration->format('Y-m-d H:i:s');

        $book = array(
            'empty' => $expiration_date,
            'expire' => $expiration,
        );

        $constraint = new Assert\Collection(array(
                'empty' => new Assert\NotBlank(),
                'expire' => new Assert\Range(array(
                    'min' => 'now',
                )),
            )
        );

        $errors = $this->app['validator']->validate($book, $constraint);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->app->json($error->getMessage(), 400);
            }
        }

        //Generate token for invitation code
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        $tokenEntity = new Entity\Tokens();

        $tokenEntity->setToken($token);
        $tokenEntity->setRoles($roles);
        $tokenEntity->setExpiration($expiration);

        $this->getRepository('Bolt\Storage\Entity\Tokens')->save($tokenEntity);

        return $token;
    }

    /**
     * Share the invitation code by email.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendLink(Request $request)
    {
        // Get all the necessary data to send the email
        $to = $request->request->get('to');
        $subject = $request->request->get('subject');
        $text = $request->request->get('message');
        $link = $request->request->get('link');

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
            )
        );

        $violationList = $this->app['validator']->validate($book, $constraint);

        if (count($violationList) > 0) {
            foreach ($violationList as $violation) {
                $field = preg_replace('/\[|\]/', "", $violation->getPropertyPath());
                $error = $violation->getMessage();
                $errors[$field] = $error;
            }
            return $this->app->json($errors, 400);
        }

        // Get the current user to use his email on the "From" field.
        $userEntity = new Entity\Users($this->getUser());

        $from = $this->app['config']->get('general/mailoptions/senderMail', $userEntity->getEmail());

        // Compile the email with the invitation link.
        $mailhtml = $this->app['render']->render(
            '@bolt/mail/invitation.twig',
            [
                'message' => $text,
                'link' => $link,
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

            return false;
        }

        return true;
    }

    /**
     * Create a form to generate an invitation code with the form builder.
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getGenerateInvitationForm()
    {
        // Start building the form
        $form = $this->createFormBuilder(FormType::class);

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
            );


        return $form;
    }

    /**
     * Create a form to send the invitation code by email with the form builder.
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getInvitationEmailForm()
    {
        // Start building the form
        $form = $this->createFormBuilder(FormType::class);

        $form
            ->add(
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

