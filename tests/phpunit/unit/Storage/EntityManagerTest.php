<?php

namespace Bolt\Tests\Storage;

use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL;

/**
 * Class to test src/Storage/EntityManager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class EntityManagerTest extends BoltUnitTest
{
    public function testConnect()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $this->assertSame($app['db'], \PHPUnit_Framework_Assert::readAttribute($em, 'conn'));
        $this->assertSame($app['dispatcher'], \PHPUnit_Framework_Assert::readAttribute($em, 'eventManager'));
    }

    public function testCreateQueryBuilder()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $qb = $em->createQueryBuilder();
        $this->assertInstanceOf(DBAL\Query\QueryBuilder::class, $qb);
    }

    public function testGetRepository()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $repo = $em->getRepository(Entity\Users::class);

        $this->assertInstanceOf(Repository::class, $repo);
    }

    public function testGetRepositoryWithAliases()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $customRepoClass = Mock\TestRepository::class;
        $em->setRepository(Entity\Users::class, $customRepoClass);
        $em->addEntityAlias('test', Entity\Users::class);

        $repo = $em->getRepository('test');

        $this->assertInstanceOf(Mock\TestRepository::class, $repo);
    }

    public function testGetDefaultRepositoryFactory()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        // The first check should work, this one should fail because the factory has not been set.
        $this->setExpectedException('RuntimeException');
        $em = new EntityManager($app['db'], $app['dispatcher'], $app['storage.metadata']);
        $repo = $em->getRepository('showcases');
    }
}
