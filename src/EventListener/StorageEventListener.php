<?php
namespace Bolt\EventListener;

use Bolt\AccessControl\Permissions;
use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Exception\AccessControlException;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Request\ProfilerAwareTrait;
use Bolt\Storage\Database\Schema;
use Bolt\Storage\Entity;
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
    /** @var integer */
    protected $hashStrength;

    /**
     * Constructor.
     *
     * @param EventProcessor\TimedRecord    $timedRecord
     * @param Schema\SchemaManagerInterface $schemaManager
     * @param UrlGeneratorInterface         $urlGenerator
     * @param FlashLoggerInterface          $loggerFlash
     * @param PasswordFactory               $passwordFactory
     * @param integer                       $hashStrength
     */
    public function __construct(
        EventProcessor\TimedRecord $timedRecord,
        Schema\SchemaManagerInterface $schemaManager,
        UrlGeneratorInterface $urlGenerator,
        FlashLoggerInterface $loggerFlash,
        PasswordFactory $passwordFactory,
        $hashStrength
    ) {
        $this->timedRecord = $timedRecord;
        $this->schemaManager = $schemaManager;
        $this->urlGenerator = $urlGenerator;
        $this->loggerFlash = $loggerFlash;
        $this->passwordFactory = $passwordFactory;
        $this->hashStrength = $hashStrength;
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
        if ($this->timedRecord->isDuePublish()) {
            $this->timedRecord->publishTimedRecords();
        }
        if ($this->timedRecord->isDueHold()) {
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
            $msg = Trans::__(
                "The database needs to be updated/repaired. Go to 'Configuration' > '<a href=\"%link%\">Check Database</a>' to do this now.",
                ['%link%' => $this->urlGenerator->generate('dbcheck')]
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
        ];
    }
}
