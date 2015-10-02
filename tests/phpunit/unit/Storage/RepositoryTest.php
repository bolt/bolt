<?php
namespace Bolt\Tests\Storage;

use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RepositoryTest extends BoltUnitTest
{
    public $eventCount = [];

    public function testConstruct()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Authtoken';

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);

        $this->assertSame($em, \PHPUnit_Framework_Assert::readAttribute($repo, 'em'));
    }

    public function testGetTableName()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Authtoken';
        $em = $app['storage'];
        $repo = $em->getRepository($entityName);

        $this->assertEquals('bolt_authtoken', $repo->getTableName());
    }

    public function testGetEntityName()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Authtoken';
        $em = $app['storage'];
        $repo = $em->getRepository($entityName);

        $this->assertEquals($entityName, $repo->getEntityName());
    }

    public function testSimpleFind()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $em = $app['storage'];
        $repo = $em->getRepository($entityName);

        $result = $repo->find(1);

        $this->assertInstanceOf($entityName, $result);
    }

    public function testFindAll()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Users';

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);
        $result = $repo->findAll();

        $this->assertTrue(is_array($result));
        foreach ($result as $obj) {
            $this->assertInstanceOf($entityName, $obj);
        }
    }

    public function testFindBy()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Users';

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);
        $result = $repo->findBy(['id' => 1]);

        $this->assertTrue(is_array($result));
        $this->assertInstanceOf($entityName, $result[0]);
    }

    public function testFindOneBy()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Users';
        $this->runListenCount($app, 'preHydrate');

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);
        $result = $repo->findOneBy(['id' => 1]);

        $this->assertInstanceOf($entityName, $result);
        $this->assertEquals(1, $this->eventCount['preHydrate']);
    }

    public function testInsert()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Users';
        $this->runListenCount($app, 'preSave');
        $this->runListenCount($app, 'postSave');

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);

        $newUser = [
            'username'    => 'test',
            'password'    => 'fake',
            'email'       => 'testuser@example.com',
            'displayname' => 'Test User',
            'lastip'      => '127.0.0.1'
        ];

        $entity = new $entityName($newUser);
        $this->assertEquals(1, $repo->save($entity));

        $result = $repo->findOneBy(['displayname' => 'Test User']);

        $this->assertInstanceOf($entityName, $result);
        $this->assertEquals('test', $result->getUsername());
        $this->assertEquals(1, $this->eventCount['preSave']);
        $this->assertEquals(1, $this->eventCount['postSave']);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Users';
        $this->runListenCount($app, 'preSave');
        $this->runListenCount($app, 'postSave');

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);
        $existing = $repo->findOneBy(['displayname' => 'Test User']);

        $existing->setUsername('testupdated');
        $em->save($existing);

        $existing2 = $repo->findOneBy(['displayname' => 'Test User']);
        $this->assertEquals('testupdated', $existing2->getUsername());
        $this->assertEquals(1, $this->eventCount['preSave']);
        $this->assertEquals(1, $this->eventCount['postSave']);
    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        $app = $this->getApp();
        $entityName = 'Bolt\Storage\Entity\Users';
        $this->runListenCount($app, 'preDelete');
        $this->runListenCount($app, 'postDelete');

        $em = $app['storage'];
        $repo = $em->getRepository($entityName);
        $existing = $repo->findOneBy(['displayname' => 'Test User']);

        $result = $repo->delete($existing);
        $confirm = $repo->findOneBy(['displayname' => 'Test User']);
        $this->assertFalse($confirm);

        $this->assertEquals(1, $this->eventCount['preDelete']);
        $this->assertEquals(1, $this->eventCount['postDelete']);
    }

    protected function runListenCount($app, $event)
    {
        $this->eventCount[$event] = 0;
        $phpunit = $this;
        $app['dispatcher']->addListener($event, function () use ($count, $phpunit, $event) {
           $count ++;
           $phpunit->eventCount[$event] = $count;
        });
    }
}
