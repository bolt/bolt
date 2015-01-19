<?php
namespace Bolt\Tests\Helper;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Helpers\Input;

/**
 * Class to test src/Helper/Input.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class InputTest extends BoltUnitTest
{


    public function testCleanPostedData()
    {
        // Test flat value
        $val = "test\r\n\v\f";
        $this->assertEquals('test    ', Input::cleanPostedData($val, false, true));
        
        // Test tabs convert to spaces
        $val = "test\t";
        $this->assertEquals('test    ', Input::cleanPostedData($val, false, true));
        
        
        // Test on array
        $vals = array(
           'first' => "test\r\n",
           'second' => "test\t"
        );
        $this->assertEquals(array('first'=>'test  ', 'second'=>'test    '), Input::cleanPostedData($vals, false, true));


        // Test Slashed
        $vals = array(
           'first' => "\\\"test\\\"",
           'second' => "a \\\"test\\\" val"
        );
        $this->assertEquals(array('first'=>'"test"', 'second'=>'a "test" val'), Input::cleanPostedData($vals, true, true));

        
    }
    
    
 
   
}

// Allows us to test magic quotes stuff
namespace Bolt\Helpers;
function get_magic_quotes_gpc(){
    return true;
}
