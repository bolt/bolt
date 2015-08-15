<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/QueryTest.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class NativeSearchTest extends BoltUnitTest
{
    public function testQueryBuild()
    {
        $app = $this->getApp();
        $this->addSomeContent();

        $results = $app['query']->getContent('pages/nativesearch', ['filter' => 'lorem ipsum']);
        
        
    }

    
}
