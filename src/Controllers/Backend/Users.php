<?php
namespace Bolt\Controllers\Backend;

use Bolt\Permissions;
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
    protected function addControllers(ControllerCollection $c)
    {
        $c->get('/users', 'controllers.backend.users:actionAdmin')
            ->bind('users');

        $c->match('/users/edit/{id}', 'controllers.backend.users:actionEdit')
            ->assert('id', '\d*')
            ->bind('useredit');

        $c->match('/userfirst', 'controllers.backend.users:actionFirst')
            ->bind('userfirst');

        $c->post('/user/{action}/{id}', 'controllers.backend.users:actionModify')
            ->bind('useraction');

        $c->match('/profile', 'controllers.backend.users:actionProfile')
            ->bind('profile');

        $c->get('/roles', 'controllers.backend.users:actionViewRoles')
            ->bind('roles');
    }

    /*
     * Routes
     */

    /**
     * All users admin page.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function actionAdmin(Request $request)
    {
        $currentuser = $this->getUser();
        $users = $this->getUsers()->getUsers();
        $sessions = $this->getUsers()->getActiveSessions();

        foreach ($users as $name => $user) {
            if (($key = array_search(Permissions::ROLE_EVERYONE, $user['roles'], true)) !== false) {
                unset($users[$name]['roles'][$key]);
            }
        }

        $context = array(
            'currentuser' => $currentuser,
            'users'       => $users,
            'sessions'    => $sessions
        );

        return $this->render('users/users.twig', $context);
    }

    /**
     * User edit route.
     *
     * @param Request $request The Symfony Request
     * @param integer $id      The user ID
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionEdit(Request $request, $id)
    {
        $currentuser = $this->getUser();

        // Get the user we want to edit (if any)
        if (!empty($id)) {
            $user = $this->getUser($id);

            // Verify the current user has access to edit this user
            if (!$this->app['permissions']->isAllowedToManipulate($user, $currentuser)) {
                $this->addFlash('error', Trans::__('You do not have the right privileges to edit that user.'));

                return $this->redirectToRoute('users');
            }
        } else {
            $user = $this->getUsers()->getEmptyUser();
        }

        $enabledoptions = array(
            1 => Trans::__('page.edit-users.activated.yes'),
            0 => Trans::__('page.edit-users.activated.no')
        );

        $roles = array_map(
            function ($role) {
                return $role['label'];
            },
            $this->app['permissions']->getDefinedRoles()
        );

        $form = $this->getUserForm($user, true);

        // New users and the current users don't need to disable themselves
        if ($currentuser['id'] != $id) {
            $form->add(
                'enabled',
                'choice',
                array(
                    'choices'     => $enabledoptions,
                    'expanded'    => false,
                    'constraints' => new Assert\Choice(array_keys($enabledoptions)),
                    'label'       => Trans::__('page.edit-users.label.user-enabled'),
                )
            );
        }

        $form
            ->add(
                'roles',
                'choice',
                array(
                    'choices'  => $roles,
                    'expanded' => true,
                    'multiple' => true,
                    'label'    => Trans::__('page.edit-users.label.assigned-roles')
                )
            )
            ->add(
                'lastseen',
                'text',
                array(
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-seen')
                )
            )
            ->add(
                'lastip',
                'text',
                array(
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-ip')
                )
            );

        // Set the validation
        $form = $this->setUserFormValidation($form, true);

        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            $user = $this->validateUserForm($request, $form, false);

            $currentuser = $this->getUser();

            if ($user !== false && $user['id'] === $currentuser['id'] && $user['username'] !== $currentuser['username']) {
                // If the current user changed their own login name, the session is effectively
                // invalidated. If so, we must redirect to the login page with a flash message.
                $this->addFlash('error', Trans::__('page.edit-users.message.change-self'));

                return $this->redirectToRoute('login');
            } elseif ($user !== false) {
                // Return to the 'Edit users' screen.
                return $this->redirectToRoute('users');
            }
        }

        /** @var \Symfony\Component\Form\FormView|\Symfony\Component\Form\FormView[] $formView */
        $formView = $form->createView();

        $manipulatableRoles = $this->app['permissions']->getManipulatableRoles($currentuser);
        foreach ($formView['roles'] as $role) {
            if (!in_array($role->vars['value'], $manipulatableRoles)) {
                $role->vars['attr']['disabled'] = 'disabled';
            }
        }

        $context = array(
            'kind'        => empty($id) ? 'create' : 'edit',
            'form'        => $formView,
            'note'        => '',
            'displayname' => $user['displayname'],
        );

        return $this->render('edituser/edituser.twig', $context);
    }

    /**
     * Create the first user.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionFirst(Request $request)
    {
        // We should only be here for creating the first user
        if ($this->app['integritychecker']->checkUserTableIntegrity() && $this->getUsers()->hasUsers()) {
            return $this->redirectToRoute('dashboard');
        }

        // Get and empty user array
        $user = $this->getUsers()->getEmptyUser();

        // Add a note, if we're setting up the first user using SQLite.
        $dbdriver = $this->getOption('general/database/driver');
        if ($dbdriver === 'sqlite' || $dbdriver === 'pdo_sqlite') {
            $note = Trans::__('page.edit-users.note-sqlite');
        } else {
            $note = '';
        }

        // If we get here, chances are we don't have the tables set up, yet.
        $this->app['integritychecker']->repairTables();

        // Grant 'root' to first user by default
        $user['roles'] = array(Permissions::ROLE_ROOT);

        // Get the form
        $form = $this->getUserForm($user, true);

        // Set the validation
        $form = $this->setUserFormValidation($form, true);

        /** @var \Symfony\Component\Form\Form */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            if ($this->validateUserForm($request, $form, true)) {
                // To the dashboard, where 'login' will be triggered
                return $this->redirectToRoute('dashboard');
            }
        }

        $context = array(
            'kind'        => 'create',
            'form'        => $form->createView(),
            'note'        => $note,
            'displayname' => $user['displayname'],
        );

        return $this->render('firstuser/firstuser.twig', $context);
    }

    /**
     * Perform modification actions on users.
     *
     * @param Request $request The Symfony Request
     * @param string  $action  The action
     * @param integer $id      The user ID
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionModify(Request $request, $action, $id)
    {
        if (!$this->checkAntiCSRFToken()) {
            $this->addFlash('info', Trans::__('An error occurred.'));

            return $this->redirectToRoute('users');
        }
        $user = $this->getUser($id);

        if (!$user) {
            $this->addFlash('error', 'No such user.');

            return $this->redirectToRoute('users');
        }

        // Prevent the current user from enabling, disabling or deleting themselves
        $currentuser = $this->getUser();
        if ($currentuser['id'] == $user['id']) {
            $this->addFlash('error', Trans::__("You cannot '%s' yourself.", array('%s', $action)));

            return $this->redirectToRoute('users');
        }

        // Verify the current user has access to edit this user
        if (!$this->app['permissions']->isAllowedToManipulate($user, $currentuser)) {
            $this->addFlash('error', Trans::__('You do not have the right privileges to edit that user.'));

            return $this->redirectToRoute('users');
        }

        switch ($action) {

            case 'disable':
                if ($this->getUsers()->setEnabled($id, 0)) {
                    $this->app['logger.system']->info("Disabled user '{$user['displayname']}'.", array('event' => 'security'));

                    $this->addFlash('info', Trans::__("User '%s' is disabled.", array('%s' => $user['displayname'])));
                } else {
                    $this->addFlash('info', Trans::__("User '%s' could not be disabled.", array('%s' => $user['displayname'])));
                }
                break;

            case 'enable':
                if ($this->getUsers()->setEnabled($id, 1)) {
                    $this->app['logger.system']->info("Enabled user '{$user['displayname']}'.", array('event' => 'security'));
                    $this->addFlash('info', Trans::__("User '%s' is enabled.", array('%s' => $user['displayname'])));
                } else {
                    $this->addFlash('info', Trans::__("User '%s' could not be enabled.", array('%s' => $user['displayname'])));
                }
                break;

            case 'delete':

                if ($this->checkAntiCSRFToken() && $this->getUsers()->deleteUser($id)) {
                    $this->app['logger.system']->info("Deleted user '{$user['displayname']}'.", array('event' => 'security'));
                    $this->addFlash('info', Trans::__("User '%s' is deleted.", array('%s' => $user['displayname'])));
                } else {
                    $this->addFlash('info', Trans::__("User '%s' could not be deleted.", array('%s' => $user['displayname'])));
                }
                break;

            default:
                $this->addFlash('error', Trans::__("No such action for user '%s'.", array('%s' => $user['displayname'])));

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
    public function actionProfile(Request $request)
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
                $user = $form->getData();

                $res = $this->getUsers()->saveUser($user);
                $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', array('%user%' => $user['displayname'])), array('event' => 'security'));
                if ($res) {
                    $this->addFlash('success', Trans::__('page.edit-users.message.user-saved', array('%user%' => $user['displayname'])));
                } else {
                    $this->addFlash('error', Trans::__('page.edit-users.message.saving-user', array('%user%' => $user['displayname'])));
                }

                return $this->redirectToRoute('profile');
            }
        }

        $context = array(
            'kind'        => 'profile',
            'form'        => $form->createView(),
            'note'        => '',
            'displayname' => $user['displayname'],
        );

        return $this->render('edituser/edituser.twig', $context);
    }

    /**
     * Route to view the configured user roles.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function actionViewRoles()
    {
        $contenttypes = $this->getOption('contenttypes');
        $permissions = array('view', 'edit', 'create', 'publish', 'depublish', 'change-ownership');
        $effectivePermissions = array();
        foreach ($contenttypes as $contenttype) {
            foreach ($permissions as $permission) {
                $effectivePermissions[$contenttype['slug']][$permission] =
                $this->app['permissions']->getRolesByContentTypePermission($permission, $contenttype['slug']);
            }
        }
        $globalPermissions = $this->app['permissions']->getGlobalRoles();

        $context = array(
            'effective_permissions' => $effectivePermissions,
            'global_permissions'    => $globalPermissions,
        );

        return $this->render('roles/roles.twig', $context);
    }

    /*
     * Helper functions
     */

    /**
     * Create a user form with the form builder.
     *
     * @param array   $user
     * @param boolean $addusername
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getUserForm(array $user, $addusername = false)
    {
        // Start building the form
        $form = $this->createFormBuilder('form', $user);

        // Username goes first
        if ($addusername) {
            $form->add(
                'username',
                'text',
                array(
                    'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                    'label'       => Trans::__('page.edit-users.label.username'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.username')
                    )
                )
            );
        }

        // Add the other fields
        $form
            ->add('id', 'hidden')
            ->add(
                'password',
                'password',
                array(
                    'required' => false,
                    'label'    => Trans::__('page.edit-users.label.password'),
                    'attr'     => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.password')
                    )
                )
            )
            ->add(
                'password_confirmation',
                'password',
                array(
                    'required' => false,
                    'label'    => Trans::__('page.edit-users.label.password-confirm'),
                    'attr'     => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.password-confirm')
                    )
                )
            )
            ->add(
                'email',
                'text',
                array(
                    'constraints' => new Assert\Email(),
                    'label'       => Trans::__('page.edit-users.label.email'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.email')
                    )
                )
            )
            ->add(
                'displayname',
                'text',
                array(
                    'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                    'label'       => Trans::__('page.edit-users.label.display-name'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.displayname')
                    )
                )
            );

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
     * @param \Symfony\Component\Form\FormBuilder $form
     * @param boolean                             $addusername
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function setUserFormValidation(FormBuilder $form, $addusername = false)
    {
        $app = $this->app;
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($app, $addusername) {
                $form = $event->getForm();
                $id = $form['id']->getData();
                $pass1 = $form['password']->getData();
                $pass2 = $form['password_confirmation']->getData();

                // If adding a new user (empty $id) or if the password is not empty (indicating we want to change it),
                // then make sure it's at least 6 characters long.
                if ((empty($id) || !empty($pass1)) && strlen($pass1) < 6) {
                    // screw it. Let's just not translate this message for now. Damn you, stupid non-cooperative
                    // translation thingy. $error = new FormError("This value is too short. It should have {{ limit }}
                    // characters or more.", array('{{ limit }}' => 6), 2);
                    $error = new FormError(Trans::__('page.edit-users.error.password-short'));
                    $form['password']->addError($error);
                }

                // Passwords must be identical.
                if ($pass1 != $pass2) {
                    $form['password_confirmation']->addError(new FormError(Trans::__('page.edit-users.error.password-mismatch')));
                }

                if ($addusername) {
                    // Usernames must be unique.
                    if (!$this->getUsers()->checkAvailability('username', $form['username']->getData(), $id)) {
                        $form['username']->addError(new FormError(Trans::__('page.edit-users.error.username-used')));
                    }
                }

                // Email addresses must be unique.
                if (!$this->getUsers()->checkAvailability('email', $form['email']->getData(), $id)) {
                    $form['email']->addError(new FormError(Trans::__('page.edit-users.error.email-used')));
                }

                // Displaynames must be unique.
                if (!$this->getUsers()->checkAvailability('displayname', $form['displayname']->getData(), $id)) {
                    $form['displayname']->addError(new FormError(Trans::__('page.edit-users.error.displayname-used')));
                }
            }
        );

        return $form;
    }

    /**
     * Handle a POST from user edit or first user creation.
     *
     * @param Request                     $request
     * @param Symfony\Component\Form\Form $form      A Symfony form
     * @param boolean                     $firstuser If this is a first user set up
     *
     * @return array|boolean An array of user elements, otherwise false
     */
    private function validateUserForm(Request $request, Form $form, $firstuser = false)
    {
        $form->submit($request->get($form->getName()));

        if ($form->isValid()) {
            $user = $form->getData();

            if ($firstuser) {
                $user['roles'] = array(Permissions::ROLE_ROOT);
            } else {
                $id = isset($user['id']) ? $user['id'] : null;
                $user['roles'] = $this->getUsers()->filterManipulatableRoles($id, $user['roles']);
            }

            $res = $this->getUsers()->saveUser($user);

            if ($user['id']) {
                $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', array('%user%' => $user['displayname'])), array('event' => 'security'));
            } else {
                $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-added', array('%user%' => $user['displayname'])), array('event' => 'security'));

                // Create a welcome email
                $mailhtml = $this->render(
                    'email/firstuser.twig',
                    array(
                        'sitename' => $this->getOption('general/sitename')
                    )
                )->getContent();

                try {
                    // Send a welcome email
                    $name = $this->getOption('general/mailoptions/senderName', $this->getOption('general/sitename'));
                    $email = $this->getOption('general/mailoptions/senderMail', $user['email']);
                    $message = $this->app['mailer']
                        ->createMessage('message')
                        ->setSubject(Trans::__('New Bolt site has been set up'))
                        ->setFrom(array($email => $name))
                        ->setTo(array($user['email']   => $user['displayname']))
                        ->setBody(strip_tags($mailhtml))
                        ->addPart($mailhtml, 'text/html');

                    $this->app['mailer']->send($message);
                } catch (\Exception $e) {
                    // Sending message failed. What else can we do, sending with snailmail?
                    $this->app['logger.system']->error("The 'mailoptions' need to be set in app/config/config.yml", array('event' => 'config'));
                }
            }

            if ($res) {
                $this->addFlash('success', Trans::__('page.edit-users.message.user-saved', array('%user%' => $user['displayname'])));
            } else {
                $this->addFlash('error', Trans::__('page.edit-users.message.saving-user', array('%user%' => $user['displayname'])));
            }

            return $user;
        }

        return false;
    }
}
