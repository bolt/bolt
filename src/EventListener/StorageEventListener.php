<?php
namespace Bolt\EventListener;

use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\Entity;
use Hautelook\Phpass\PasswordHash;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorageEventListener implements EventSubscriberInterface
{
    /** @var string */
    protected $hashStrength;

    /**
     * Constructor.
     *
     * @param string $hashStrength
     */
    public function __construct($hashStrength)
    {
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
        if ($usersEntity->getPassword() && $usersEntity->getPassword() !== '**dontchange**') {
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
