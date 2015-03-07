<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;

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
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        $this->assertSame($app['db'], \PHPUnit_Framework_Assert::readAttribute($em, 'conn'));
        $this->assertSame($app['db.event_manager'], \PHPUnit_Framework_Assert::readAttribute($em, 'eventManager'));        
        
    }
    
    public function testCreateQueryBuilder()
    {
        $app = $this->getApp();
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        
        $qb = $em->createQueryBuilder();
        $this->assertInstanceOf('Doctrine\DBAL\Query\QueryBuilder', $qb);
    }
    
    public function testGetRepository()
    {
        $app = $this->getApp();
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        
        $repo = $em->getRepository('Bolt\Storage\Entity\Users');
        
        $this->assertInstanceOf('Bolt\Storage\Repository', $repo);
    }
    
}
