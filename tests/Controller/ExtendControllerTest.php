<?php
namespace Bolt\Tests\Controller;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Tests\BoltUnitTest;
use Bolt\Controllers\Extend;
use Bolt\Composer\CommandRunner;

/**
 * Class to test correct operation of src/Controllers/Extend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class ExtendControllerTest extends BoltUnitTest
{

    
    public function testDefaultRegistries()
    {
        
        $app = $this->getApp();
        $this->assertNotEmpty($app['extend.site']);
        $this->assertNotEmpty($app['extend.repo']);
        $this->assertInstanceOf(CommandRunner::class, $app['extend.runner']);
        
    }




}
