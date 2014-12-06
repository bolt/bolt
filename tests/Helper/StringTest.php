<?php
namespace Bolt\Tests\Helper;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Helpers\String;

/**
 * Class to test src/Helper/String.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StringTest extends BoltUnitTest
{


    public function testMakeSafe()
    {
        
        // basic
        $input = "this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this is a uis s", String::makeSafe($input));
        
        //strict
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals("this-is-a-uis-s", String::makeSafe($input, true));
        
        // extra chars
        $input = ";this is a ƃuıɹʇs ʇsǝʇ";
        $this->assertEquals(";this-is-a-uis-s", String::makeSafe($input, true, ';'));
    }
    
    public function testSlug()
    {
        $slug = "This is a title";
        $this->assertEquals("this-is-a-title", String::slug($slug));
        
        // Test on array input
        $slug = array('title'=>'This is a title', 'id'=>35);
        $this->assertEquals("this-is-a-title-35", String::slug($slug));
    }
    
    public function testReplaceFirst()
    {
        $input = "this is a test string this is a test string";
        $this->assertEquals("one is a test string this is a test string", String::replaceFirst('this','one',$input));
    }
    
    
    
 
   
}