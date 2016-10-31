<?php

namespace Bolt\Tests\EventListener;

use Bolt\EventListener\StorageEventListener;
use Bolt\Events\StorageEvent;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Database\Schema\SchemaManagerInterface;
use Bolt\Storage\Entity\Users;
use Bolt\Storage\EventProcessor\TimedRecord;
use PasswordLib\Password\Factory as PasswordFactory;
use PasswordLib\Password\Implementation\Blowfish;
use Prophecy\Argument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StorageEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Users */
    private $user;
    /** @var TimedRecord */
    private $timedRecord;
    /** @var SchemaManagerInterface */
    private $schemaManager;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var FlashLoggerInterface */
    private $flashLogger;
    /** @var PasswordFactory */
    private $passwordFactory;
    /** @var StorageEventListener */
    private $listener;
    /** @var StorageEvent */
    private $storageEvent;

    public function setUp()
    {
        $this->user = $this->prophesize(Users::class);

        $this->timedRecord = $this->prophesize(TimedRecord::class);
        $this->schemaManager = $this->prophesize(SchemaManagerInterface::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->flashLogger = $this->prophesize(FlashLoggerInterface::class);
        $this->passwordFactory = $this->prophesize(PasswordFactory::class);

        $this->listener = new StorageEventListener(
            $this->timedRecord->reveal(),
            $this->schemaManager->reveal(),

            $this->urlGenerator->reveal(),
            $this->flashLogger->reveal(),
            $this->passwordFactory->reveal(),
            5
        );

        $this->storageEvent = $this->prophesize(StorageEvent::class);
    }

    /**
     * It should throw an exception if the password is shorter than 6 characters.
     *
     * @expectedException \Bolt\Exception\AccessControlException
     * @expectedExceptionMessage Can not save a password with a length shorter than 6 characters!
     */
    public function testOnPreSavePasswordTooShort()
    {
        $this->user->getPassword()->willReturn('pass');
        $this->storageEvent->getContent()->willReturn($this->user->reveal());

        $this->listener->onUserEntityPreSave($this->storageEvent->reveal());
    }

    /**
     * It should hash the users password.
     */
    public function testOnPreSaveSetPasswordHash()
    {
        $this->storageEvent->getContent()->willReturn($this->user->reveal());
        $this->user->getPassword()->willReturn('password');
        $this->passwordFactory->createHash('password', Argument::type('string'))->willReturn('hashedpassword');
        $this->user->setPassword('hashedpassword')->shouldBeCalled();

        $this->listener->onUserEntityPreSave($this->storageEvent->reveal());
    }

    /**
     * It should detect already hashed passwords.
     *
     * @dataProvider providePreSaveAlreadyHashed
     */
    public function testOnPreSavePasswordAlreadyHashed($hash)
    {
        $this->storageEvent->getContent()->willReturn($this->user->reveal());
        $this->user->getPassword()->willReturn($hash);
        $this->passwordFactory->createHash(Argument::cetera())->shouldNotBeCalled();
        $this->user->setPassword($hash)->shouldBeCalled();

        $this->listener->onUserEntityPreSave($this->storageEvent->reveal());
    }

    public function providePreSaveAlreadyHashed()
    {
        return [
            [
                Blowfish::getPrefix() . '07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi'
            ],
            [
                '$P$ABCDEFGHIJKLMNOPQRSTUVWXYZ01234',
            ]
        ];

    }
}
