<?php
namespace Bolt\Controller\Backend;

use Bolt\AccessControl\Permissions;
use Bolt\Events\AccessControlEvent;
use Bolt\Form\FormType;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Backend controller for user maintenance routes.
 *
 * Prior to v3.0 this functionality primarily existed in the monolithic
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

        $c->match('/invitation/{code}', 'invitation')
            ->bind('invitation');
    }

    /**
     * All users admin page.
     *
     * @return \Bolt\Response\TemplateResponse
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
            'sessions'    => $sessions,
        ];

        return $this->render('@bolt/users/users.twig', $context);
    }

    /**
     * User edit route.
     *
     * @param Request $request The Symfony Request
     * @param integer $id      The user ID
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id)
    {
        $currentUser = $this->getUser();
        $userEntity = $this->getUserEntity($id);
        if ($userEntity === false) {
            return $this->redirectToRoute('users');
        }

        // Generate the form
        $form = $this->createFormBuilder(FormType\UserEditType::class, $userEntity)
            ->getForm()
            ->handleRequest($request)
        ;

        if ($form->isValid()) {
            $userEntity = $form->getData();
            $saved = $this->getRepository(Entity\Users::class)->save($userEntity);
            if ($saved) {
                $this->flashes()->success(Trans::__('page.edit-users.message.user-saved', ['%user%' => $userEntity->getDisplayname()]));
                $this->app['logger.system']->info(
                    Trans::__('page.edit-users.log.user-updated', ['%user%' => $userEntity->getDisplayname()]),
                    ['event' => 'security']
                );
            } else {
                $this->flashes()->error(Trans::__('page.edit-users.message.saving-user', ['%user%' => $userEntity->getDisplayname()]));
            }

            if ($userEntity !== false && $userEntity->getId() === $currentUser->getId() && $userEntity->getUsername() !== $currentUser->getUsername()) {
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

        $context = [
            'kind'        => $id ? 'edit' : 'create',
            'form'        => $form->createView(),
            'note'        => '',
            'displayname' => $userEntity->getDisplayname(),
        ];

        return $this->render('@bolt/edituser/edituser.twig', $context);
    }

    /**
     * Create the first user.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function first(Request $request)
    {
        // We should only be here for creating the first user
        if ($this->app['schema']->hasUserTable() && $this->users()->hasUsers()) {
            return $this->redirectToRoute('dashboard');
        }

        // Add a note, if we're setting up the first user using SQLite.
        $dbdriver = $this->getOption('general/database/driver');
        if ($dbdriver === 'sqlite' || $dbdriver === 'pdo_sqlite') {
            $note = Trans::__('page.edit-users.note-sqlite');
        } else {
            $note = '';
        }

        // If we get here, chances are we don't have the tables set up, yet.
        $this->app['schema']->update();

        // Get an new entity object
        $userEntity = $this->getUserEntity();
        $userEntity->setEnabled(true);
        // Grant 'root' to first user by default
        $userEntity->setRoles([Permissions::ROLE_ROOT]);

        // Generate the form
        $form = $this->createFormBuilder(FormType\UserNewType::class, $userEntity)
            ->getForm()
            ->handleRequest($request)
        ;

        if ($form->isValid()) {
            $userEntity = $form->getData();
            $saved = $this->getRepository(Entity\Users::class)->save($userEntity);
            if ($saved) {
                $this->app['logger.system']->info(
                    Trans::__('page.edit-users.log.user-added', ['%user%' => $userEntity->getDisplayname()]),
                    ['event' => 'security']
                );
                $this->notifyUserSetupEmail($request, $userEntity->getDisplayname(), $userEntity->getEmail());

                $event = new AccessControlEvent($request);
                $login = $this->login()->login($userEntity->getUsername(), $form->get('password')->getData(), $event);
                $token = $this->session()->get('authentication');
                if ($login && $token) {
                    $this->flashes()->clear();
                    $this->flashes()->success(Trans::__('general.bolt-welcome-new-site', ['%USER%' => $userEntity->getDisplayname()]));



                    /** @var RedirectResponse $response */
                    $response = $this->setAuthenticationCookie($request, $this->redirectToRoute('dashboard'), (string) $token);

                    return $response;
                }
                if (!$token) {
                    $this->flashes()->error(Trans::__('general.phrase.error-session-data-login'));
                } else {
                    $this->flashes()->error(Trans::__('general.phrase.something-went-wrong-after-first-user'));
                }
            }
        }

        $context = [
            'kind'        => 'create',
            'form'        => $form->createView(),
            'note'        => $note,
            'displayname' => $userEntity->getDisplayname(),
            'sitename'    => $this->getOption('general/sitename'),
        ];

        return $this->render('@bolt/firstuser/firstuser.twig', $context);
    }

    /**
     * Create user using invitation code.
     *
     * @param Request $request
     * @param String $code The invitation code provided
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invitation(Request $request, $code)
    {
        // Get Repository of available invitations
        $invitationsRepository = $this->getRepository('Bolt\Storage\Entity\Invitations');

        // Check if the invitation code exists and it is still available
        $invitation = $invitationsRepository->findOneBy(array('token' => $code));

        if ((!$invitation) || (new \DateTime() > new \DateTime($invitation[0]->expiration))) {
            return $this->render('@bolt/invitation/invitationfail.twig');
        }

        $tokenEntity = new Entity\Invitations($invitation[0]);

        // Get an empty user
        $userEntity = new Entity\Users();

        // Set the roles of the invitation code
        $userEntity->setRoles($tokenEntity->getRoles());

        // Get the form
        $form = Users::getUserForm($userEntity, true);

        // Set the validation
        $form = $this->setUserFormValidation($form, true);

        /** @var \Symfony\Component\Form\Form */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST') && $response = $this->firstPost($request, $form, true)) {
            $invitationsRepository->delete($invitation);
            return $response;
        }

        $context = [
            'kind' => 'create',
            'form' => $form->createView(),
            'sitename' => $this->getOption('general/sitename'),
        ];

        return $this->render('@bolt/invitation/invitation.twig', $context);
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
        if (!$this->isCsrfTokenValid()) {
            $this->flashes()->error(Trans::__('general.phrase.something-went-wrong'));

            return $this->redirectToRoute('users');
        }

        if (!$user = $this->getUser($id)) {
            $this->flashes()->error('No such user.');

            return $this->redirectToRoute('users');
        }

        // Prevent the current user from enabling, disabling or deleting themselves
        $currentuser = $this->getUser();
        if ($currentuser->getId() == $user->getId()) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-self-action', ['%s', $action]));

            return $this->redirectToRoute('users');
        }

        // Verify the current user has access to edit this user
        if (!$this->app['permissions']->isAllowedToManipulate($user->toArray(), $currentuser->toArray())) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-privilege-edit-user'));

            return $this->redirectToRoute('users');
        }

        switch ($action) {
            case 'disable':
                if ($this->users()->setEnabled($id, false)) {
                    $this->app['logger.system']->info("Disabled user '{$user->getDisplayname()}'.", ['event' => 'security']);

                    $this->flashes()->info(Trans::__('general.phrase.user-disabled', ['%s' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->info(Trans::__('general.phrase.user-failed-disabled', ['%s' => $user->getDisplayname()]));
                }
                break;

            case 'enable':
                if ($this->users()->setEnabled($id, true)) {
                    $this->app['logger.system']->info("Enabled user '{$user->getDisplayname()}'.", ['event' => 'security']);
                    $this->flashes()->info(Trans::__('general.phrase.user-enabled', ['%s' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->info(Trans::__('general.phrase.user-failed-enable', ['%s' => $user->getDisplayname()]));
                }
                break;

            case 'delete':
                if ($this->isCsrfTokenValid() && $this->users()->deleteUser($id)) {
                    $this->app['logger.system']->info("Deleted user '{$user->getDisplayname()}'.", ['event' => 'security']);
                    $this->flashes()->info(Trans::__('general.phrase.user-deleted', ['%s' => $user->getDisplayname()]));
                } else {
                    $this->flashes()->info(Trans::__('general.phrase.user-failed-delete', ['%s' => $user->getDisplayname()]));
                }
                break;

            default:
                $this->flashes()->error(Trans::__('general.phrase.no-such-action-for-user', ['%s' => $user->getDisplayname()]));

        }

        return $this->redirectToRoute('users');
    }

    /**
     * User profile page route.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function profile(Request $request)
    {
        $userEntity = $this->getUser();
        if ($userEntity === false) {
            return $this->redirectToRoute('users');
        }

        // Generate the form
        $form = $this->createFormBuilder(FormType\UserProfileType::class, $userEntity)
            ->getForm()
            ->handleRequest($request)
        ;

        if ($form->isValid()) {
            $userEntity = $form->getData();
            $this->app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', ['%user%' => $userEntity->getDisplayname()]), ['event' => 'security']);
            if ($this->getRepository('Bolt\Storage\Entity\Users')->save($userEntity)) {
                $this->flashes()->success(Trans::__('page.edit-users.message.user-saved', ['%user%' => $userEntity->getDisplayname()]));
            } else {
                $this->flashes()->error(Trans::__('page.edit-users.message.saving-user', ['%user%' => $userEntity->getDisplayname()]));
            }

            return $this->redirectToRoute('profile');
        }

        $context = [
            'kind'        => 'profile',
            'form'        => $form->createView(),
            'note'        => '',
            'displayname' => $userEntity->getDisplayname(),
        ];

        return $this->render('@bolt/edituser/edituser.twig', $context);
    }

    /**
     * Route to view the configured user roles.
     *
     * @return \Bolt\Response\TemplateResponse
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
     * Get the user we want to edit, or a new entity object if null.
     *
     * @param integer $id
     *
     * @return Entity\Users|false
     */
    private function getUserEntity($id = null)
    {
        if (empty($id)) {
            return new Entity\Users();
        } elseif (!$userEntity = $this->getUser($id)) {
            $this->flashes()->error(Trans::__('general.phrase.user-not-exist'));

            return false;
        }

        $currentUser = $this->getUser();
        if (!$this->app['permissions']->isAllowedToManipulate($userEntity->toArray(), $currentUser->toArray())) {
            // Verify the current user has access to edit this user
            $this->flashes()->error(Trans::__('general.phrase.access-denied-privilege-edit-user'));

            return false;
        }

        return $userEntity;
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
                ->setSubject(Trans::__('general.bolt-new-site-set-up'))
                ->setFrom($from)
                ->setReplyTo($from)
                ->setTo([$email   => $displayName])
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html');

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
