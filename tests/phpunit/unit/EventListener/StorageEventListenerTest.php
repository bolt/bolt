<?php

namespace Bolt\Tests\EventListener;

use Bolt\AccessControl\PasswordHashManager;
use Bolt\EventListener\StorageEventListener;
use Bolt\Events\StorageEvent;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Database\Schema\SchemaManagerInterface;
use Bolt\Storage\Entity\Users;
use Bolt\Storage\EntityManager;
use Bolt\Storage\EventProcessor\TimedRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StorageEventListenerTest extends TestCase
{
    /** @var Users */
    private $user;
    /** @var EntityManager */
    private $em;
    /** @var TimedRecord */
    private $timedRecord;
    /** @var SchemaManagerInterface */
    private $schemaManager;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var FlashLoggerInterface */
    private $flashLogger;
    /** @var PasswordHashManager */
    private $passwordHash;
    /** @var StorageEventListener */
    private $listener;
    /** @var StorageEvent */
    private $storageEvent;

    public function setUp()
    {
        $this->user = $this->prophesize(Users::class);

        $this->em = $this->prophesize(EntityManager::class);
        $this->timedRecord = $this->prophesize(TimedRecord::class);
        $this->schemaManager = $this->prophesize(SchemaManagerInterface::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->flashLogger = $this->prophesize(FlashLoggerInterface::class);
        $this->passwordHash = $this->prophesize(PasswordHashManager::class);

        $this->listener = new StorageEventListener(
            $this->em->reveal(),
            $this->timedRecord->reveal(),
            $this->schemaManager->reveal(),
            $this->urlGenerator->reveal(),
            $this->flashLogger->reveal(),
            $this->passwordHash->reveal(),
            5,
            false
        );

        $this->storageEvent = $this->prophesize(StorageEvent::class);
    }

    /**
     * It should hash the users password.
     */
    public function testOnPreSaveSetPasswordHash()
    {
        $this->storageEvent->getContent()->willReturn($this->user->reveal());
        $this->user->getPassword()->willReturn('password');
        $this->passwordHash->createHash('password')->willReturn('hashedpassword');
        $this->user->setPassword('hashedpassword')->shouldBeCalled();

        $this->listener->onUserEntityPreSave($this->storageEvent->reveal());
    }
}
