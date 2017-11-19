<?php

namespace Bolt\EventListener;

use Bolt\AccessControl\Permissions;
use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Exception\AccessControlException;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Request\ProfilerAwareTrait;
use Bolt\Storage\Collection;
use Bolt\Storage\Database\Schema;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManagerInterface;
use Bolt\Storage\EventProcessor;
use Bolt\Translation\Translator as Trans;
use PasswordLib\Password\Factory as PasswordFactory;
use PasswordLib\Password\Implementation as Password;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StorageEventListener implements EventSubscriberInterface
{
    use ProfilerAwareTrait;

    /** @var EntityManagerInterface */
    protected $em;
    /** @var EventProcessor\TimedRecord */
    protected $timedRecord;
    /** @var Schema\SchemaManagerInterface */
    protected $schemaManager;
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var \Bolt\Logger\FlashLoggerInterface */
    protected $loggerFlash;
    /** @var PasswordFactory */
    protected $passwordFactory;
    /** @var int */
    protected $hashStrength;
    /** @var bool */
    protected $timedRecordsEnabled;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface        $em
     * @param EventProcessor\TimedRecord    $timedRecord
     * @param Schema\SchemaManagerInterface $schemaManager
     * @param UrlGeneratorInterface         $urlGenerator
     * @param FlashLoggerInterface          $loggerFlash
     * @param PasswordFactory               $passwordFactory
     * @param int                           $hashStrength
     * @param bool                          $timedRecordsEnabled
     */
    public function __construct(
        EntityManagerInterface $em,
        EventProcessor\TimedRecord $timedRecord,
        Schema\SchemaManagerInterface $schemaManager,
        UrlGeneratorInterface $urlGenerator,
        FlashLoggerInterface $loggerFlash,
        PasswordFactory $passwordFactory,
        $hashStrength,
        $timedRecordsEnabled
    ) {
        $this->em = $em;
        $this->timedRecord = $timedRecord;
        $this->schemaManager = $schemaManager;
        $this->urlGenerator = $urlGenerator;
        $this->loggerFlash = $loggerFlash;
        $this->passwordFactory = $passwordFactory;
        $this->hashStrength = $hashStrength;
        $this->timedRecordsEnabled = $timedRecordsEnabled;
    }

    /**
     * Pre-save storage event for user entities.
     *
     * @param StorageEvent $event
     */
    public function onUserEntityPreSave(StorageEvent $event)
    {
        /** @var Entity\Users $entityRecord */
        $entityRecord = $event->getContent();

        if ($entityRecord instanceof Entity\Users) {
            $this->passwordHash($entityRecord);
        }
    }

    /**
     * Post hydration storage event.
     *
     * @param HydrationEvent $event
     */
    public function onPostHydrate(HydrationEvent $event)
    {
        $entity = $event->getSubject();
        if (!$entity instanceof Entity\Users) {
            return;
        }

        // Ensure Permissions::ROLE_EVERYONE always exists
        $roles = $entity->getRoles();
        if (!in_array(Permissions::ROLE_EVERYONE, $roles)) {
            $roles[] = Permissions::ROLE_EVERYONE;
            $entity->setRoles($roles);
        }
    }

    /**
     * Pre-delete event to delete an entities taxonomy & relation entities.
     *
     * @param StorageEvent $event
     */
    public function onPreDelete(StorageEvent $event)
    {
        $entity = $event->getContent();
        if (!$entity instanceof Entity\Content) {
            return;
        }
        $taxonomies = $entity->getTaxonomy();
        if ($taxonomies instanceof Collection\Taxonomy) {
            $repo = $this->em->getRepository(Entity\Taxonomy::class);
            foreach ($taxonomies as $taxonomy) {
                $repo->delete($taxonomy);
            }
        }
        $relations = $entity->getRelation();
        if ($relations instanceof Collection\Relations) {
            $repo = $this->em->getRepository(Entity\Relations::class);
            foreach ($relations as $relation) {
                $repo->delete($relation);
            }
        }
    }

    /**
     * Kernel request listener callback.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if ($this->isProfilerRequest($event->getRequest())) {
            return;
        }

        $this->schemaCheck($event);

        // Check if we need to 'publish' any 'timed' records, or 'hold' any expired records.
        if ($this->timedRecordsEnabled && $this->timedRecord->isDuePublish()) {
            $this->timedRecord->publishTimedRecords();
        }
        if ($this->timedRecordsEnabled && $this->timedRecord->isDueHold()) {
            $this->timedRecord->holdExpiredRecords();
        }
    }

    /**
     * Trigger database schema checks if required.
     *
     * @param GetResponseEvent $event
     */
    protected function schemaCheck(GetResponseEvent $event)
    {
        $session = $event->getRequest()->getSession();
        $validSession = $session->isStarted() && $session->get('authentication');
        $expired = $this->schemaManager->isCheckRequired();

        // Don't show the check if we're in the dbcheck already.
        $notInCheck = !in_array(
            $event->getRequest()->get('_route'),
            ['dbupdate', '_wdt']
        );

        if ($validSession && $expired && $this->schemaManager->isUpdateRequired() && $notInCheck) {
            $msg = sprintf(
                '%s > \'<a href="%s">%s</a>\' %s',
                Trans::__('general.phrase.database-update-required-pre'),
                $this->urlGenerator->generate('dbcheck'),
                Trans::__('general.phrase.database-check'),
                Trans::__('general.phrase.database-update-required-post')
            );
            $this->loggerFlash->error($msg);
        }
    }

    /**
     * Hash user passwords on save.
     *
     * @param Entity\Users $usersEntity
     */
    protected function passwordHash(Entity\Users $usersEntity)
    {
        if ($usersEntity->getPassword() !== null) {
            $usersEntity->setPassword($this->getValidHash($usersEntity->getPassword()));
        }
    }

    /**
     * Return a valid hash for a password, of if the password is already hashed
     * just return as is.
     *
     * @param string $password
     *
     * @throws AccessControlException
     *
     * @return string
     */
    private function getValidHash($password)
    {
        if (Password\Blowfish::detect($password)) {
            return $password;
        }
        if (Password\PHPASS::detect($password)) {
            return $password;
        }
        if (strlen($password) < 6) {
            throw new AccessControlException('Can not save a password with a length shorter than 6 characters!');
        }

        return $this->passwordFactory->createHash($password, '$2y$');
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST       => ['onKernelRequest', 31],
            StorageEvents::PRE_SAVE     => ['onUserEntityPreSave', Application::EARLY_EVENT],
            StorageEvents::POST_HYDRATE => 'onPostHydrate',
            StorageEvents::PRE_DELETE   => 'onPreDelete',
        ];
    }
}
