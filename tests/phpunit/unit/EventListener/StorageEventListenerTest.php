<?php

namespace Bolt\Tests\EventListener;

use Bolt\Storage\Entity\Users;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Database\Schema\Manager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Bolt\Logger\FlashLoggerInterface;
use PasswordLib\Password\Factory as PasswordFactory;
use Bolt\EventListener\StorageEventListener;
use Bolt\Events\StorageEvent;
use Bolt\Config;
use Prophecy\Argument;
use PasswordLib\Password\Implementation\Blowfish;

class StorageEventListenerTest extends \PHPUnit_Framework_TestCase
{
    private $user;
    private $entityManager;
    private $config;
    private $manager;
    private $urlGenerator;
    private $flashLogger;
    private $passwordFactory;
    private $listener;
    private $storageEvent;

    public function setUp()
    {
        $this->user = $this->prophesize(Users::class);
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->config = $this->prophesize(Config::class);
        $this->manager = $this->prophesize(Manager::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->flashLogger = $this->prophesize(FlashLoggerInterface::class);
        $this->passwordFactory = $this->prophesize(PasswordFactory::class);

        $this->listener = new StorageEventListener(
            $this->entityManager->reveal(),
            $this->config->reveal(),
            $this->manager->reveal(),
            $this->urlGenerator->reveal(),
            $this->flashLogger->reveal(),
            $this->passwordFactory->reveal(),
            5,
            true
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

        $this->listener->onPreSave($this->storageEvent->reveal());
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

        $this->listener->onPreSave($this->storageEvent->reveal());
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

        $this->listener->onPreSave($this->storageEvent->reveal());
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
