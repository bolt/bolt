<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\CacheClear;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/CacheClear.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class CacheClearTest extends BoltUnitTest
{


    public function testSuccessfulClear()
    {
        $app = $this->getApp();
        $app['cache'] = $this->getCacheMock();
        $command = new CacheClear($app);
        $tester = new CommandTester($command);
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegExp('/Deleted 1 file/', $result);
        
        
        
    }
    
    public function testWithFailures()
    {
        $app = $this->getApp();
        $app['cache'] = $this->getCacheMock('bad');
        $command = new CacheClear($app);
        $tester = new CommandTester($command);
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegExp('/files could not be deleted/', $result);
        $this->assertRegExp('/test.txt/', $result);
        
        
        
    }
    
    
    protected function getCacheMock($type = 'good')
    {
        $good = array('successfiles'=>1, 'failedfiles'=>0);
        $bad = array('successfiles'=>0, 'failedfiles'=>1, 'failed'=>array('test.txt'));
        
        $cache = $this->getMock('Bolt\Cache', array('clearCache'));
        $cache->expects($this->any())
            ->method('clearCache')
            ->will($this->returnValue($$type));
        return $cache;
    }
    
 
   
}