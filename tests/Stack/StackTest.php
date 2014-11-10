<?php
namespace Bolt\Tests\Stack;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Stack;
use Bolt\Users;

/**
 * Class to test src/Field/Base.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StackTest extends BoltUnitTest
{


    public function testSetup()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('getCurrentUser','saveUser'), array($app));
        $app['users'] = $users;
        $stack = new Stack($app);
        $stack->add('mytestfile');
        
        $this->assertTrue($stack->isOnStack('mytestfile'));
        
        $stack->delete('mytestfile');
        $this->assertFalse($stack->isOnStack('mytestfile'));
        
        $this->assertFalse($stack->isStackable('mytestfile'));
        $this->assertTrue($stack->isStackable('mytestfile.png'));

    }
    
    public function testDuplicates()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('getCurrentUser','saveUser'), array($app));
        $app['users'] = $users;
        $stack = new Stack($app);
        $stack->add('mytestfile');
        $stack->add('mytestfile');
        $this->assertTrue($stack->isOnStack('mytestfile'));
    }
    
    public function testListFilter()
    {
        $app = $this->makeApp();
        $app['resources']->setPath('files', TEST_ROOT . '/tests/resources/stack');
        $app->initialize();

        $users = $this->getMock('Bolt\Users', array('getCurrentUser','saveUser'), array($app));
        $app['users'] = $users;
        $stack = new Stack($app);
        
        $stack->add('files/testing.md');
        $stack->add('files/testing.txt');
        $stack->add('files/test.jpg');
        $stack->add('files/test2.jpg');
        
        $items = $stack->listItems(100,'image');
        $this->assertEquals(2, count($items));
        
        $items = $stack->listItems(100,'document');
        $this->assertEquals(2, count($items));

    
    }
    

    
    
 
   
}