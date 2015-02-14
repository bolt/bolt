<?php
namespace Bolt\Tests\Logger;

use Bolt\Tests\BoltUnitTest;
use Bolt\Logger\ChangeLogItem;
/**
 * Class to test src/Logger/ChangeLogItem.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ChangeLogItemTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        $cl = new ChangeLogItem($app, array('id'=>5,'title'=>'test'));
    }

    public function testGetters()
    {
        $app = $this->getApp();
        $cl = new ChangeLogItem($app, array('id'=>5,'title'=>'test'));
        $this->assertTrue(isset($cl->mutation_type));
        $this->assertFalse(isset($cl->nonexistent));
        $this->assertEquals(5, $cl->id);
        $this->setExpectedException('InvalidArgumentException');
        $test = $cl->nonexistent;
    }
    
    public function testGetMutationType()
    {
        $app = $this->getApp();
        $cl = new ChangeLogItem($app, array('id'=>5,'title'=>'test', 'mutation_type'=>"UPDATE"));
        
        $this->assertEquals("UPDATE", $cl->mutation_type);
    }
}

