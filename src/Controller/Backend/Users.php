<?php
namespace Bolt\Controller\Backend;

use Bolt\AccessControl\Permissions;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Backend controller for user maintenance routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Users extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/users', 'admin')
            ->bind('users');

        $c->match('/users/edit/{id}', 'edit')
            ->assert('id', '\d*')
            ->bind('useredit');

        $c->match('/userfirst', 'first')
            ->bind('userfirst');

        $c->post('/user/{action}/{id}', 'modify')
            ->bind('useraction');

        $c->match('/profile', 'profile')
            ->bind('profile');

        $c->get('/roles', 'viewRoles')
            ->bind('roles');
    }

    /**
     * All users admin page.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function admin()
    {
        $currentuser = $this->getUser();
        $users = $this->users()->getUsers();
        $sessions = $this->accessControl()->getActiveSessions();

        foreach ($users as $name => $user) {
            if (($key = array_search(Permissions::ROLE_EVERYONE, $user['roles'], true)) !== false) {
                unset($users[$name]['roles'][$key]);
            }
        }

        $context = [
            'currentuser' => $currentuser,
            'users'       => $users,
            'sessions'    => $sessions
        ];

        return $this->render('@bolt/users/users.twig', $context);
    }

    /**
     * User edit route.
     *
     * @param Request $request The Symfony Request
     * @param integer $id      The user ID
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id)
    {
        if (!$userEntity = $this->getEditableUser($id)) {
            return $this->redirectToRoute('users');
        }

        // Get the base form
        $form = $this->getUserForm($userEntity, true);

        // Get the extra editable fields
        $form = $this->getUserEditFields($form, $id);

        // Set the validation
        $form = $this->setUserFormValidation($form, true);

        // Generate the form
        $form = $form->getForm();

        $currentUser = $this->getUser();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            $userEntity = $this->validateUserForm($request, $form, false);

            if ($userEntity !== false && $userEntity->getId() == $currentUser->getId() && $userEntity->getUsername() !== $currentUser->getUsername()) {
                // If the current user changed their own login name, the session
                // is effectively invalidated. If so, we must redirect to the
                // login page with a flash message.
                $this->flashes()->error(Trans::__('page.edit-users.message.change-self'));

                return $this->redirectToRoute('login');
            } elseif ($userEntity !== false) {
                // Return to the 'Edit users' screen.
                return $this->redirectToRoute('users');
            }
        }

        /** @var \Symfony\Component\Form\FormView|\Symfony\Component\Form\FormView[] $formView */
        $formView = $form->createView();

        $manipulatableRoles = $this->app['permissions']->getManipulatableRoles($currentUser->toArray());
        foreach ($formView['roles'] as $role) {
            if (!in_array($role->vars['value'], $manipulatableRoles)) {
                $role->vars['attr']['disabled'] = 'disabled';
            }
        }

        $context = [
            'kind'        => empty($id) ? 'create' : 'edit',
            'form'        => $formView,
            'note'        => '',
            'displayname' => $userEntity['displayname'],
        ];

        return $this->render('@bolt/edituser/edituser.twig', $context);
    }

    /**
     * Create the first user.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function first(Request $request)
    {
        // We should only be here for creating the first user
        if ($this->app['schema']->checkUserTableIntegrity() && $this->users()->hasUsers()) {
            return $this->redirectToRoute('dashboard');
        }

        // Get and empty user
        $userEntity = new Entity\Users();

        // Add a note, if we're setting up the first user using SQLite.
        $dbdriver = $this->getOption('general/database/driver');
        if ($dbdriver === 'sqlite' || $dbdriver === 'pdo_sqlite') {
            $note = Trans::__('page.edit-users.note-sqlite');
        } else {
            $note = '';
        }

        // If we get here, chances are we don't have the tables set up, yet.
        $this->app['schema']->repairTables();

        // Grant 'root' to first user by default
        $userEntity->setRoles([Permissions::ROLE_ROOT]);

        // Get the form
        $form = $this->getUserForm($userEntity, true);

        // Set the validation
        $form = $this->setUserFormValidation($form, true);

        /** @var \Symfony\Component\Form\Form */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST') && $response = $this->firstPost($request, $form)) {
            return $response;
        }

        $context = [
            'kind'        => 'create',
            'form'        => $form->createView(),
            'note'        => $note,
            'displayname' => $userEntity['displayname'],
            'sitename'    => $this->getOption('general/sitename'),
        ];

        return $this->render('@bolt/firstuser/firstuser.twig', $context);
    }

    /**
     * Perform modification actions on users.
     *
     * @param string  $action The action
     * @param integer $id     The user ID
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function modify($action, $id)
    {
        if (!$this->checkAntiCSRFToken()) {
            $this->flashes()->info(Trans::__('An error occurred.'));

            return $this->redirectToRoute('users');
        }

        if (!$user = $this->getUser($id)) {
            $this->flashes()->error('No such user.');

            return $this->redirectToRoute('users');
        }

        // Prevent the current user from enabling, disabling or deleting themselves
        $currentuser = $this->getUser();
        if ($currentuser->getId() == $user->getId()) {
            $this->flashes()->error(Trans::__("You cannot '%s' yourself.", ['%s', $action]));

            return $this->redirectToRoute('users');
        }

        // Verify the current user has access to edit this user
        if (!$this->app['permissions']->isAllowedToManipulate($user->toArray(), $currentuser->toArray())) {
            $this->flashes()->error(Trans::__('You do not have the right privileges to edit that user.'));

            return $this->redirectToRoute('users');
        }

        switch ($action) {

            case 'disable':
                if ($this->users()->setEnabled($id, false)) {
                    $this->app['logger.system']->info("Disabled user '{$user->getDisplayname()}'.", ['event' => 'security']);

                    $this->flashes()->info(Trans::__("User '%s' is disabled.", ['%s' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->info(Trans::__("User '%s' could not be disabled.", ['%s' => $user->getDisplayname()]));
                }
                break;

            case 'enable':
                if ($this->users()->setEnabled($id, true)) {
                    $this->app['logger.system']->info("Enabled user '{$user->getDisplayname()}'.", ['event' => 'security']);
                    $this->flashes()->info(Trans::__("User '%s' is enabled.", ['%s' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->info(Trans::__("User '%s' could not be enabled.", ['%s' => $user->getDisplayname()]));
                }
                break;

            case 'delete':

                if ($this->checkAntiCSRFToken() && $this->users()->deleteUser($id)) {
                    $this->app['logger.system']->info("Deleted user '{$user->getDisplayname()}'.", ['event' => 'security']);
                    $this->flashes()->info(Trans::__("User '%s' is deleted.", ['%s' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->info(Trans::__("User '%s' could not be deleted.", ['%s' => $user->getDisplayname()]));
                }
                break;

            default:
                $this->flashes()->error(Trans::__("No such action for user '%s'.", ['%s' => $user->getDisplayname()]));

        }

        return $this->redirectToRoute('users');
    }

    /**
     * User profile page route.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function profile(Request $request)
    {
        $user = $this->getUser();

        // Get the form
        $form = $this->getUserForm($user, false);

        // Set the validation
        $form = $this->setUserFormValidation($form, false);

        /** @var \Symfony\Component\Form\Form */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            $form->submit($request->get($form->getName()));

            if ($form->isValid()) {
                $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', ['%user%' => $user->getDisplayname()]), ['event' => 'security']);

                $user = new Entity\Users($form->getData());
                if ($this->getRepository('Bolt\Storage\Entity\Users')->save($user)) {
                    $this->flashes()->success(Trans::__('page.edit-users.message.user-saved', ['%user%' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->error(Trans::__('page.edit-users.message.saving-user', ['%user%' => $user->getDisplayname()]));
                }

                return $this->redirectToRoute('profile');
            }
        }

        $context = [
            'kind'        => 'profile',
            'form'        => $form->createView(),
            'note'        => '',
            'displayname' => $user->getDisplayname(),
        ];

        return $this->render('@bolt/edituser/edituser.twig', $context);
    }

    /**
     * Route to view the configured user roles.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function viewRoles()
    {
        $contenttypes = $this->getOption('contenttypes');
        $permissions = $this->app['permissions']->getContentTypePermissions();
        $effectivePermissions = [];
        foreach ($contenttypes as $contenttype) {
            foreach (array_keys($permissions) as $permission) {
                $effectivePermissions[$contenttype['slug']][$permission] =
                $this->app['permissions']->getRolesByContentTypePermission($permission, $contenttype['slug']);
            }
        }
        $globalPermissions = $this->app['permissions']->getGlobalRoles();

        $context = [
            'effective_permissions' => $effectivePermissions,
            'global_permissions'    => $globalPermissions,
        ];

        return $this->render('@bolt/roles/roles.twig', $context);
    }

    /**
     * Handle a first user creation POST.
     *
     * @param Request $request
     * @param Form    $form
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|false
     */
    private function firstPost(Request $request, Form $form)
    {
        if (!$userEntity = $this->validateUserForm($request, $form, true)) {
            return false;
        }

        $login = $this->login()->login($request, $userEntity->getUsername(), $form->get('password')->getData());
        $token = $this->session()->get('authentication');
        if ($login && $token) {
            $this->flashes()->clear();
            $this->flashes()->info(Trans::__('Welcome to your new Bolt site, %USER%.', ['%USER%' => $userEntity->getDisplayname()]));

            $response = $this->setAuthenticationCookie($this->redirectToRoute('dashboard'), (string) $token);

            return $response;
        }

        if (!$token) {
            $this->flashes()->error(Trans::__("Unable to retrieve login session data. Please check your system's PHP session settings."));
        } else {
            $this->flashes()->error(Trans::__('Something went wrong with logging in after first user creation!'));
        }

        return false;
    }

    /**
     * Create a user form with the form builder.
     *
     * @param Entity\Users $user
     * @param boolean      $addusername
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getUserForm(Entity\Users $user, $addusername = false)
    {
        // Start building the form
        $form = $this->createFormBuilder('form', $user);

        // Username goes first
        if ($addusername) {
            $form->add(
                'username',
                'text',
                [
                    'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 32])],
                    'label'       => Trans::__('page.edit-users.label.username'),
                    'attr'        => [
                        'placeholder' => Trans::__('page.edit-users.placeholder.username')
                    ]
                ]
            );
        }

        // Add the other fields
        $form
            ->add('id', 'hidden')
            ->add(
                'password',
                'password',
                [
                    'required' => false,
                    'label'    => Trans::__('page.edit-users.label.password'),
                    'attr'     => [
                        'placeholder' => Trans::__('page.edit-users.placeholder.password')
                    ]
                ]
            )
            ->add(
                'password_confirmation',
                'password',
                [
                    'required' => false,
                    'label'    => Trans::__('page.edit-users.label.password-confirm'),
                    'attr'     => [
                        'placeholder' => Trans::__('page.edit-users.placeholder.password-confirm')
                    ]
                ]
            )
            ->add(
                'email',
                'text',
                [
                    'constraints' => new Assert\Email(),
                    'label'       => Trans::__('page.edit-users.label.email'),
                    'attr'        => ['placeholder' => Trans::__('page.edit-users.placeholder.email')]
                ]
            )
            ->add(
                'displayname',
                'text',
                [
                    'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 32])],
                    'label'       => Trans::__('page.edit-users.label.display-name'),
                    'attr'        => ['placeholder' => Trans::__('page.edit-users.placeholder.displayname')]
                ]
            );

        return $form;
    }

    /**
     * Get the user we want to edit (if any).
     *
     * @param integer $id
     *
     * @return Entity\Users|false
     */
    private function getEditableUser($id)
    {
        if (empty($id)) {
            return new Entity\Users;
        } elseif (!$userEntity = $this->getUser($id)) {
            $this->flashes()->error(Trans::__('That user does not exist.'));

            return false;
        }

        $currentUser = $this->getUser();
        if (!$this->app['permissions']->isAllowedToManipulate($userEntity->toArray(), $currentUser->toArray())) {
            // Verify the current user has access to edit this user
            $this->flashes()->error(Trans::__('You do not have the right privileges to edit that user.'));

            return false;
        }

        return $userEntity;
    }

    /**
     * Get the editable fields for the user form.
     *
     * @param FormBuilder $form
     * @param integer     $id
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getUserEditFields(FormBuilder $form, $id)
    {
        $enabledoptions = [
            1 => Trans::__('page.edit-users.activated.yes'),
            0 => Trans::__('page.edit-users.activated.no')
        ];

        $roles = array_map(
            function ($role) {
                return $role['label'];
            },
            $this->app['permissions']->getDefinedRoles()
        );

        // New users and the current users don't need to disable themselves
        $currentUser = $this->getUser();
        if ($currentUser->getId() != $id) {
            $form->add(
                'enabled',
                'choice',
                [
                    'choices'     => $enabledoptions,
                    'expanded'    => false,
                    'constraints' => new Assert\Choice(array_keys($enabledoptions)),
                    'label'       => Trans::__('page.edit-users.label.user-enabled'),
                ]
            );
        }

        $form
            ->add(
                'roles',
                'choice',
                [
                    'choices'  => $roles,
                    'expanded' => true,
                    'multiple' => true,
                    'label'    => Trans::__('page.edit-users.label.assigned-roles')
                ]
            )
            ->add(
                'lastseen',
                'datetime',
                [
                    'widget'   => 'single_text',
                    'format'   => 'yyyy-MM-dd HH:mm:ss',
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-seen')
                ]
            )
            ->add(
                'lastip',
                'text',
                [
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-ip')
                ]
            )
        ;

        return $form;
    }

    /**
     * Validate the user form.
     *
     * Use a custom validator to check:
     *   * Passwords are identical
     *   * Username is unique
     *   * Email is unique
     *   * Displaynames are unique
     *
     * @param FormBuilder $form
     * @param boolean     $addusername
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function setUserFormValidation(FormBuilder $form, $addusername = false)
    {
        $users = $this->users();
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($addusername, $users) {
                $form = $event->getForm();
                $id = $form['id']->getData();
                $pass1 = $form['password']->getData();
                $pass2 = $form['password_confirmation']->getData();

                // If adding a new user (empty $id) or if the password is not empty (indicating we want to change it),
                // then make sure it's at least 6 characters long.
                if ((empty($id) || !empty($pass1)) && strlen($pass1) < 6) {
                    $error = new FormError(Trans::__('page.edit-users.error.password-short'));
                    $form['password']->addError($error);
                }

                // Passwords must be identical.
                if ($pass1 != $pass2) {
                    $form['password_confirmation']->addError(new FormError(Trans::__('page.edit-users.error.password-mismatch')));
                }

                if ($addusername) {
                    // Password must be different from username
                    $username = strtolower($form['username']->getData());
                    if (!empty($username) && strtolower($pass1) === $username) {
                        $form['password']->addError(new FormError(Trans::__('page.edit-users.error.password-different-username')));
                    }

                    // Password must not be contained in the display name
                    $displayname = strtolower($form['displayname']->getData());
                    if (!empty($displayname) && strrpos($displayname, strtolower($pass1)) !== false) {
                        $form['password']->addError(new FormError(Trans::__('page.edit-users.error.password-different-displayname')));
                    }

                    // Usernames must be unique.
                    if (!$users->checkAvailability('username', $form['username']->getData(), $id)) {
                        $form['username']->addError(new FormError(Trans::__('page.edit-users.error.username-used')));
                    }
                }

                // Email addresses must be unique.
                if (!$users->checkAvailability('email', $form['email']->getData(), $id)) {
                    $form['email']->addError(new FormError(Trans::__('page.edit-users.error.email-used')));
                }

                // Displaynames must be unique.
                if (!$users->checkAvailability('displayname', $form['displayname']->getData(), $id)) {
                    $form['displayname']->addError(new FormError(Trans::__('page.edit-users.error.displayname-used')));
                }
            }
        );

        return $form;
    }

    /**
     * Handle a POST from user edit or first user creation.
     *
     * @param Request $request
     * @param Form    $form      A Symfony form
     * @param boolean $firstuser If this is a first user set up
     *
     * @return Entity\Users|false
     */
    private function validateUserForm(Request $request, Form $form, $firstuser = false)
    {
        $form->submit($request->get($form->getName()));
        if (!$form->isValid()) {
            return false;
        }

        $userEntity = new Entity\Users($form->getData());
        $userEntity->setUsername($this->app['slugify']->slugify($userEntity->getUsername()));

        if (!$firstuser) {
            $userEntity->setRoles($this->users()->filterManipulatableRoles($userEntity->getId(), $userEntity->getRoles()));
        }

        if ($this->getRepository('Bolt\Storage\Entity\Users')->save($userEntity)) {
            $this->flashes()->success(Trans::__('page.edit-users.message.user-saved', ['%user%' => $userEntity->getDisplayname()]));
            $this->notifyUserSave($request, $userEntity->getDisplayname(), $userEntity->getEmail(), $firstuser);
        } else {
            $this->flashes()->error(Trans::__('page.edit-users.message.saving-user', ['%user%' => $userEntity->getDisplayname()]));
        }

        return $userEntity;
    }

    /**
     * Notify of save event.
     *
     * @param Request $request
     * @param string  $displayName
     * @param string  $email
     * @param boolean $firstuser
     */
    private function notifyUserSave(Request $request, $displayName, $email, $firstuser)
    {
        if (!$firstuser) {
            $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', ['%user%' => $displayName]),
                ['event' => 'security']);
        } else {
            $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-added', ['%user%' => $displayName]),
                ['event' => 'security']);
            $this->notifyUserSetupEmail($request, $displayName, $email);
        }
    }

    /**
     * Send a welcome email to test mail settings.
     *
     * @param Request $request
     * @param string  $displayName
     * @param string  $email
     */
    private function notifyUserSetupEmail(Request $request, $displayName, $email)
    {
        // Create a welcome email
        $mailhtml = $this->render(
            '@bolt/email/firstuser.twig',
            ['sitename' => $this->getOption('general/sitename')]
        )->getContent();

        try {
            // Send a welcome email
            $name = $this->getOption('general/mailoptions/senderName', $this->getOption('general/sitename'));
            $from = ['bolt@' . $request->getHost() => $name];
            $email = $this->getOption('general/mailoptions/senderMail', $email);
            $message = $this->app['mailer']
                ->createMessage('message')
                ->setSubject(Trans::__('New Bolt site has been set up'))
                ->setFrom($from)
                ->setReplyTo($from)
                ->setTo([$email   => $displayName])
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html')
            ;
            $failedRecipients = [];

            $this->app['mailer']->send($message, $failedRecipients);

            // Try and send immediately
            $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);
        } catch (\Exception $e) {
            // Sending message failed. What else can we do, send via snailmail?
            $this->app['logger.system']->error("The 'mailoptions' need to be set in app/config/config.yml", ['event' => 'config']);
        }
    }
}
