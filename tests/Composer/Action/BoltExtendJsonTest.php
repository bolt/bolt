<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\Action\BoltExtendJson;

/**
 * Class to test src/Composer/Action/BoltExtendJson.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BoltExtendJsonTest extends BoltUnitTest
{

    public function setup()
    {
        
    }

    public function tearDown()
    {
        
    }

    public function testConstructWithOptions()
    {
        $options = array('name'=>'test');
        $action = new BoltExtendJson($options);
        $this->assertSame($options, $action->getOptions());
        
    }

    
    
}
