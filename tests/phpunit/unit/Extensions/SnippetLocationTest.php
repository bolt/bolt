<?php

namespace Bolt\Tests\Extensions;

use Bolt\Extensions\Snippets\Location;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Extensions/StatService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SnippetLocationTest extends BoltUnitTest
{
    /**
     * @group legacy
     */
    public function testListAll()
    {
        $location = new Location();
        $this->assertGreaterThan(1, $location->listAll());
    }
}
