<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Bolt\Storage\Hydrator;
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
        $entityName = 'Bolt\Entity\Authtoken';
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        
        $this->assertSame($em, \PHPUnit_Framework_Assert::readAttribute($repo, 'em'));        
    }
    
    public function testGetTableName()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Authtoken';
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);

        $this->assertEquals('bolt_authtoken', $repo->getTableName());
    }
    
    
    public function testGetEntityName()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Authtoken';
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        
        $this->assertEquals($entityName, $repo->getEntityName());
    }
    
    public function testSimpleFind()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Entity\Users';
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        
        $result = $repo->find(1);

        $this->assertInstanceOf($entityName, $result);
    }
    
    public function testFindAll()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Users';
        
        
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        $result = $repo->findAll();
        
        $this->assertTrue(is_array($result));
        foreach ($result as $obj) {
            $this->assertInstanceOf($entityName, $obj); 
        }
    }
    
    public function testFindBy()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Users';
        
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        $result = $repo->findBy(array('id'=>1));
        
        $this->assertTrue(is_array($result));
        $this->assertInstanceOf($entityName, $result[0]); 

    }
    
    public function testFindOneBy()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Users';
        
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        $result = $repo->findOneBy(array('id'=>1));
                
        $this->assertInstanceOf($entityName, $result); 

    }
    
    public function testInsert()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Users';
        
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        
        $newUser = array(
            'username' => 'test',
            'password' => 'fake',
            'email' => 'test@example.com',
            'displayname' => 'Test User',
            'lastip' => '127.0.0.1'
        );

        $entity = new $entityName($newUser);
        $this->assertEquals(1, $repo->save($entity));
        
        $result = $repo->findOneBy(array('displayname'=>'Test User'));
        $this->assertInstanceOf($entityName, $result);
        $this->assertEquals('test', $result->getUsername());
                        
    }
    
    public function testUpdate()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Entity\Users';
        
        $em = new EntityManager($app['db'], $app['dispatcher']);
        $repo = new Repository($em, $entityName);
        $existing = $repo->findOneBy(array('displayname'=>'Test User'));
        $existing->setUsername('testupdated');
        $em->save($existing);
        
        $existing2 = $repo->findOneBy(array('displayname'=>'Test User'));
        $this->assertEquals('testupdated', $existing2->getUsername());
    }
    

    
}
