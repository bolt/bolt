<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Bolt\Storage\Entity\AuthToken;

/**
 * Class to test src/Storage/Repository.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RepositoryTest extends BoltUnitTest
{
    
    public function testConstruct()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Authtoken';
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        $repo = new Repository($em, $entityName);
        
        $this->assertSame($em, \PHPUnit_Framework_Assert::readAttribute($repo, 'em'));        
    }
    
    public function testGetTableName()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Authtoken';
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        $repo = new Repository($em, $entityName);

        $this->assertEquals('bolt_authtoken', $repo->getTableName());
    }
    
    
    public function testGetEntityName()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Authtoken';
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        $repo = new Repository($em, $entityName);
        
        $this->assertEquals($entityName, $repo->getEntityName());
    }
    
    public function testSimpleFind()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $em = new EntityManager($app['db'], $app['db.event_manager']);
        $repo = new Repository($em, $entityName);
        
        $result = $repo->find(1);
        print_r($result); exit;
    }
    

    
}
