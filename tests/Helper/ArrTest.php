<?php
namespace Bolt\Tests\Helper;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Helpers\Arr;

/**
 * Class to test src/Helper/Arr.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ArrTest extends BoltUnitTest
{


    public function testMakeValuePairs()
    {
        $test = array(
            array('id'=>1,'value'=>1),    
            array('id'=>2,'value'=>2),    
        );
        $this->assertEquals(array(1=>1,2=>2), Arr::makeValuePairs($test, 'id', 'value'));
        $this->assertEquals(array(0=>1,1=>2), Arr::makeValuePairs($test, '', 'value'));
    }
    
    public function testMergeRecusrsiveDistinct()
    {
        $arr1 = array('key' => 'orig value');
        $arr2 = array('key' => 'new value');
        $this->assertEquals(array('key'=>'new value'), Arr::mergeRecursiveDistinct($arr1, $arr2));
        
        // Needs an exclusion for accept_file_types
        $arr1 = array('accept_file_types' => 'jpg');
        $arr2 = array('accept_file_types' => 'jpg,png');
        Arr::mergeRecursiveDistinct($arr1, $arr2);
        $this->assertEquals(array('accept_file_types'=>'jpg'), $arr1);
        
        // Test Recusrsion
        $arr1 = array('key' => array('test'=>'new value'));
        $arr2 = array('key' => array('test'=>'nested new value'));
        $this->assertEquals(array('key'=>array('test'=>'nested new value')), Arr::mergeRecursiveDistinct($arr1, $arr2));
    }
    

    
    
 
   
}