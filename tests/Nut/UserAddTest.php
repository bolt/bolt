<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\UserAdd;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/UserAdd.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class UserAddTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();  
        $app['users'] = $this->getUserMock($app);      
        $command = new UserAdd($app);
        $tester = new CommandTester($command);
        
        $tester->execute(array(
            'username'=>'test',
            'displayname'=>'Test',
            'email'=>'test@example.com',
            'password'=>'test',
            'role'=>'admin'    
        ));
        $result = $tester->getDisplay();
        $this->assertEquals('Successfully created user: test', trim($result));

    }
    
    public function testAvailability()
    {
        $app = $this->getApp();  
        $app['users'] = $this->getUserMock($app, false);                  
        $command = new UserAdd($app);
        $tester = new CommandTester($command);
        
        $tester->execute(array(
            'username'=>'test',
            'displayname'=>'Test',
            'email'=>'test@example.com',
            'password'=>'test',
            'role'=>'admin'    
        ));
        $result = $tester->getDisplay();
        $this->assertRegexp("/username test already exists/", trim($result));
        $this->assertRegexp("/email test@example\.com exists/", trim($result));
        $this->assertRegexp("/display name Test already exists/", trim($result));
    }
    
    public function testFailure()
    {
        $app = $this->getApp();  
        $app['users'] = $this->getUserMock($app, true, false);                  
        $command = new UserAdd($app);
        $tester = new CommandTester($command);
        
        $tester->execute(array(
            'username'=>'test',
            'displayname'=>'Test',
            'email'=>'test@example.com',
            'password'=>'test',
            'role'=>'admin'    
        ));
        $result = $tester->getDisplay();
        $this->assertEquals("Error creating user: test", trim($result));
    }
    
    
    protected function getUserMock($app, $availability = true, $save = true)
    {        
        $users = $this->getMock('Bolt\Users', array('getUsers', 'checkAvailability', 'saveUser'), array($app));
        $users->expects($this->any())
            ->method('getUsers')
            ->will($this->returnValue(array()));
            
        $users->expects($this->any())
            ->method('checkAvailability')
            ->will($this->returnValue($availability));
            
        $users->expects($this->any())
            ->method('saveUser')
            ->will($this->returnValue($save));
            
        return $users;
    }
 
   
}