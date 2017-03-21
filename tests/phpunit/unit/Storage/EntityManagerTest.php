<?php

namespace Bolt\Tests\Storage;

use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Storage\Mock\TestRepository;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Assert;

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
        $this->assertSame($app['db'], Assert::readAttribute($em, 'conn'));
        $this->assertSame($app['dispatcher'], Assert::readAttribute($em, 'eventManager'));
    }

    public function testCreateQueryBuilder()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $qb = $em->createQueryBuilder();
        $this->assertInstanceOf(QueryBuilder::class, $qb);
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

        $em->setRepository(Entity\Users::class, TestRepository::class);
        $em->addEntityAlias('test', Entity\Users::class);

        $repo = $em->getRepository('test');

        $this->assertInstanceOf(TestRepository::class, $repo);
    }

    public function testGetDefaultRepositoryFactory()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        $this->assertInstanceOf(Repository\ContentRepository::class, $repo);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to handle unmapped data without a defaultRepositoryFactory set
     */
    public function testGetDefaultRepositoryFactoryNotSet()
    {
        $app = $this->getApp();
        // The first check should work, this one should fail because the factory has not been set.
        $em = new EntityManager($app['db'], $app['dispatcher'], $app['storage.metadata']);
        $em->getRepository('showcases');
    }
}
