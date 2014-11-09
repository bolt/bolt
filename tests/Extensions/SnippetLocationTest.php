<?php
namespace Bolt\Tests\Extensions;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Extensions\Snippets\Location;

/**
 * Class to test src/Extensions/StatService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class SnippetLocationTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        $location = new Location();        
        $this->assertGreaterThan(1, $location->listAll());
    }

    
   
}