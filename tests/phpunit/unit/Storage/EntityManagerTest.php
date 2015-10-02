<?php
namespace Bolt\Tests\Storage;

use Bolt\Storage\EntityManager;
use Bolt\Tests\BoltUnitTest;

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
        $this->assertInstanceOf('Doctrine\DBAL\Query\QueryBuilder', $qb);
    }

    public function testGetRepository()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $repo = $em->getRepository('Bolt\Storage\Entity\Users');

        $this->assertInstanceOf('Bolt\Storage\Repository', $repo);
    }

    public function testGetRepositoryWithAliases()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $customRepoClass = 'Bolt\Tests\Storage\Mock\TestRepository';
        $em->setRepository('Bolt\Storage\Entity\Users', $customRepoClass);
        $em->addEntityAlias('test', 'Bolt\Storage\Entity\Users');

        $repo = $em->getRepository('test');

        $this->assertInstanceOf('Bolt\Tests\Storage\Mock\TestRepository', $repo);
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
