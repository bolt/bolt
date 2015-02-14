<?php
namespace Bolt\Tests\Logger;

use Bolt\Tests\BoltUnitTest;
use Bolt\Logger\Handler\RecordChangeHandler;
use Bolt\Tests\Mocks\DoctrineMockBuilder;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Logger/Handler/RecordChangeHandler.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class RecordChangeHandlerTest extends BoltUnitTest
{

    public function testSetupInitialize()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        
        $log = new Logger('logger.system');
        $handler = new RecordChangeHandler($app);
        
        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $app['db'] = $db;
        
        $log->pushHandler($handler);
        $log->addRecord(Logger::DEBUG, 'test', array(
            'action'=>'UPDATE',
            'old'=>array('title'=>'test'),
            'new'=>array('title'=>'test2')
        ));
        $this->assertEquals('bolt_log_change', \PHPUnit_Framework_Assert::readAttribute($handler, 'tablename'));

    }
    
    public function testHandle()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        
        $log = new Logger('logger.system');
        $handler = new RecordChangeHandler($app);
        
        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $db->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('bolt_log_change'),
                $this->callback(function($arg) {
                    return isset($arg['diff']) && $arg['diff'] === '{"title":["test","test2"]}';
                })
        );  
        
        $app['db'] = $db;
        
        $log->pushHandler($handler);
        $log->addRecord(Logger::DEBUG, 'test', array(
            'action'=>'UPDATE',
            'comment'=>'An Update',
            'old'=>array('title'=>'test'),
            'new'=>array('title'=>'test2')
        ));
    }

    public function testNotHandling()
    {
        $app = $this->getApp();
        $handler = new RecordChangeHandler($app, Logger::WARNING);
        $this->assertFalse($handler->handle(array('level'=>100)));
    }

    
}

