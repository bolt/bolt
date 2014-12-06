<?php
namespace Bolt\Tests\Helper;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Helpers\Html;

/**
 * Class to test src/Helper/Html.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class HtmlTest extends BoltUnitTest
{


    public function testTrimText()
    {
        
        // Simple text
        $input = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.';
        $this->assertEquals("Lorem ipsum", Html::trimText($input, 11, false));
        $this->assertEquals("Lorem ipsum…", Html::trimText($input, 12, true));
        
        // Make sure tags are stripped
        $input = 'Lorem <strong>ipsum</strong> dolor sit amet, consectetur adipisicing elit.';
        $this->assertEquals("Lorem ipsum", Html::trimText($input, 11, false));
    }
    
    public function testDecorateTT()
    {
        $input = 'Lorem `ipsum` dolor.';
        $this->assertEquals("Lorem <tt>ipsum</tt> dolor.", Html::decorateTT($input));
    }
    
    
    
 
   
}