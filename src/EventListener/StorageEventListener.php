<?php
namespace Bolt\EventListener;

use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Hautelook\Phpass\PasswordHash;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorageEventListener implements EventSubscriberInterface
{
    /** @var string */
    protected $hashStrength;
    /** @var \Bolt\Storage\EntityManager */
    protected $em;

    /**
     * Constructor.
     *
     * @param string        $hashStrength
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, $hashStrength)
    {
        $this->em = $em;
        $this->hashStrength = $hashStrength;
    }

    /**
     * Pre-save storage event.
     *
     * @param StorageEvent $event
     */
    public function onPreSave(StorageEvent $event)
    {
        /** @var Entity\Entity $entityRecord */
        $entityRecord = $event->getContent();
        if ($entityRecord instanceof Entity\Users) {
            $this->passwordHash($entityRecord);
        }
    }

    /**
     * Hash user passwords on save.
     *
     * Hashstrength has a default of '10', don't allow less than '8'.
     *
     * @param Entity\Users $usersEntity
     */
    protected function passwordHash(Entity\Users $usersEntity)
    {
        if ($usersEntity->getShadowSave()) {
            return;
        } elseif ($usersEntity->getPassword() && $usersEntity->getPassword() !== '**dontchange**') {
            $hasher = new PasswordHash($this->hashStrength, true);
            $usersEntity->setPassword($hasher->HashPassword($usersEntity->getPassword()));
        } else {
            unset($usersEntity->password);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::PRE_SAVE => 'onPreSave',
        ];
    }
}
