<?php

namespace Bolt\Tests\Form\FormType;

use Bolt\Form\FormType\UserData;
use Bolt\Storage\Entity\Users;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Form\FormType\UserData
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserDataTest extends TestCase
{
    public function testApplyToEntity()
    {
        $entity = $this->createEntity();
        $dto = UserData::createFromEntity($entity)
            ->setEnabled(false)
            ->setStack([])
            ->setLastIp('::1/128')
            ->setDisplayName('Julian L. Dropbear')
            ->setLastSeen(Carbon::createFromTimestamp(1234))
            ->setEmail('julian@dropbear.com.au')
            ->setUserName('julian')
            ->setPassword('top$ecret')
            ->setRoles(['sushi', 'egg'])
            ->setEnabled(false)
        ;
        $dto->applyToEntity($entity);

        self::assertSame(42, $dto->getId());
        self::assertSame('julian', $dto->getUserName());
        self::assertSame('top$ecret', $dto->getPassword());
        self::assertSame('julian@dropbear.com.au', $dto->getEmail());
        self::assertSame('Julian L. Dropbear', $dto->getDisplayName());
        self::assertSame((string) Carbon::createFromTimestamp(1234), (string) $dto->getLastSeen());
        self::assertSame('::1/128', $dto->getLastIp());
        self::assertSame([], $dto->getStack());
        self::assertSame(false, $dto->isEnabled());
        self::assertSame(['sushi', 'egg'], $dto->getRoles());
    }

    public function providerDto()
    {
        yield [UserData::createFromEntity($this->createEntity())];
    }
    /**
     * @dataProvider providerDto
     */
    public function testGetId(UserData $dto)
    {
        self::assertSame(42, $dto->getId());
    }

    /**
     * @dataProvider providerDto
     */
    public function testUserName(UserData $dto)
    {
        self::assertSame('kenny', $dto->getUserName());
        self::assertSame('bruce', $dto->setUserName('bruce')->getUserName());
    }

    /**
     * @dataProvider providerDto
     */
    public function testPassword(UserData $dto)
    {
        self::assertSame('Dr0pß3@r', $dto->getPassword());
        self::assertSame('hunter42', $dto->setPassword('hunter42')->getPassword());
    }

    /**
     * @dataProvider providerDto
     */
    public function testEmail(UserData $dto)
    {
        self::assertSame('kenny@koala.com.au', $dto->getEmail());
        self::assertSame('bruce@koala.com.au', $dto->setEmail('bruce@koala.com.au')->getEmail());
    }

    /**
     * @dataProvider providerDto
     */
    public function testDisplayName(UserData $dto)
    {
        self::assertSame('Kenny J. Koala', $dto->getDisplayName());
        self::assertSame('Bruce M. Koala', $dto->setDisplayName('Bruce M. Koala')->getDisplayName());
    }

    /**
     * @dataProvider providerDto
     */
    public function testLastSeen(UserData $dto)
    {
        self::assertSame((string) Carbon::createFromTimestamp(0), (string) $dto->getLastSeen());
        self::assertSame((string) Carbon::createFromTimestamp(42), (string) $dto->setLastSeen(Carbon::createFromTimestamp(42))->getLastSeen());
    }

    /**
     * @dataProvider providerDto
     */
    public function testLastIp(UserData $dto)
    {
        self::assertSame('127.0.0.1', $dto->getLastIp());
        self::assertSame('::1/128', $dto->setLastIp('::1/128')->getLastIp());
    }

    /**
     * @dataProvider providerDto
     */
    public function testStack(UserData $dto)
    {
        self::assertSame(['rosie.jpg'], $dto->getStack());
        self::assertSame([], $dto->setStack([])->getStack());
    }

    /**
     * @dataProvider providerDto
     */
    public function testEnabled(UserData $dto)
    {
        self::assertSame(true, $dto->isEnabled());
        self::assertSame(false, $dto->setEnabled(false)->isEnabled());
    }

    /**
     * @dataProvider providerDto
     */
    public function testRoles(UserData $dto)
    {
        self::assertSame(['salad', 'ham'], $dto->getRoles());
        self::assertSame(['turkey', 'potato'], $dto->setRoles(['turkey', 'potato'])->getRoles());
    }

    private function createEntity()
    {
        return new Users([
            'id'          => 42,
            'username'    => 'kenny',
            'password'    => 'Dr0pß3@r',
            'email'       => 'kenny@koala.com.au',
            'lastseen'    => Carbon::createFromTimestamp(0),
            'lastip'      => '127.0.0.1',
            'displayname' => 'Kenny J. Koala',
            'stack'       => ['rosie.jpg'],
            'enabled'     => true,
            'roles'       => ['salad', 'ham'],
        ]);
    }
}
