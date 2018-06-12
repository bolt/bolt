<?php

namespace Bolt\EventListener;

use Bolt\AccessControl\Token\Token;
use Bolt\Common\Serialization;
use Bolt\Events\AccessControlEvent;
use Bolt\Events\AccessControlEvents;
use Bolt\Events\StorageEvent;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Session\SessionStorage;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManagerInterface;
use Bolt\Storage\Repository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * AccessControl listener class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessControlListener implements EventSubscriberInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;
    /** @var SessionStorage */
    protected $sessionStorage;
    /** @var EntityManagerInterface */
    protected $em;
    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /**
     * Constructor.
     *
     * @param FilesystemInterface    $filesystem
     * @param SessionStorage         $sessionStorage
     * @param EntityManagerInterface $em
     */
    public function __construct(
        FilesystemInterface $filesystem,
        SessionStorage $sessionStorage,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->filesystem = $filesystem;
        $this->sessionStorage = $sessionStorage;
        $this->em = $em;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Remove sessions & authtokens when a user is disabled.
     *
     * @param StorageEvent $event
     */
    public function onStorageEventPostSave(StorageEvent $event)
    {
        /** @var Entity\Users $userEntity */
        $userEntity = $event->getContent();
        if (!$userEntity instanceof Entity\Users) {
            return;
        }

        if (!$userEntity->isEnabled()) {
            $this->deleteAuthtokens($userEntity);
            $this->deleteSessions($userEntity);
        }
    }

    /**
     * Remove sessions & authtokens when a user is deleted.
     *
     * @param StorageEvent $event
     */
    public function onStorageEventPreDelete(StorageEvent $event)
    {
        /** @var Entity\Users $userEntity */
        $userEntity = $event->getContent();
        if (!$userEntity instanceof Entity\Users) {
            return;
        }

        $this->deleteAuthtokens($userEntity);
        $this->deleteSessions($userEntity);
    }

    /**
     * Fake the form to ensure the content editing CSRF token exists in a
     * persistent session.
     *
     * @param AccessControlEvent $event
     */
    public function onLoginSuccess(AccessControlEvent $event)
    {
        $this->csrfTokenManager->getToken('content_edit');
    }


    /**
     * Delete any save authtokens for a user.
     *
     * @param Entity\Users $user
     */
    private function deleteAuthtokens(Entity\Users $user)
    {
        /** @var Repository\AuthtokenRepository $repo */
        $repo = $this->em->getRepository(Entity\Authtoken::class);
        $repo->deleteTokens($user->getId());
    }

    /**
     * Delete active session files for a user.
     *
     * @param Entity\Users $user
     */
    private function deleteSessions(Entity\Users $user)
    {
        $savePath = $this->sessionStorage->getOptions()->get('save_path');
        try {
            $sessionFiles = $this->filesystem->find()->files()->in($savePath);
        } catch (FileNotFoundException $e) {
            return;
        }

        /** @var \Bolt\Filesystem\Handler\File $sessionFile */
        foreach ($sessionFiles as $sessionFile) {
            $data = Serialization::parse($sessionFile->read());
            if (!isset($data['_sf2_attributes']['authentication'])) {
                continue;
            }
            if (!$data['_sf2_attributes']['authentication'] instanceof Token) {
                continue;
            }
            /** @var \Bolt\AccessControl\Token\Token $token */
            $token = $data['_sf2_attributes']['authentication'];
            if ($token->getUser()->getId() === $user->getId()) {
                $sessionFile->delete();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            //\Bolt\Events\StorageEvents::PRE_DELETE => 'onStorageEventPostSave',
            //\Bolt\Events\StorageEvents::PRE_DELETE => 'onStorageEventPreDelete',
            AccessControlEvents::LOGIN_SUCCESS => 'onLoginSuccess',
        ];
    }
}
