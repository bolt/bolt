<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\LogClear;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\Helperset;
use Symfony\Component\Console\Helper\DialogHelper;

/**
 * Class to test src/Nut/LogClear.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class LogClearTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();        
        $command = new LogClear($app);
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream("Yes\n"));
        $command->setHelperset(new Helperset(array($dialog)));
        $tester = new CommandTester($command);
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegexp('/Activity logs cleared/', $result);
        
        

    }
    
    // public function testCancel()
    // {
    //     $app = $this->getApp();
    //     // Test abort
    //     $command = new LogClear($app);
    //     $command->setHelperset(new Helperset(array(new DialogHelper)));
    //     $dialog = $command->getHelper('dialog');
    //     $dialog->setInputStream($this->getInputStream("Test\n"));
    //     $tester = new CommandTester($command);
        
    //     $tester->execute(array());
    //     $result = $tester->getDisplay();
    //     $this->assertNotRegexp('/Activity logs cleared/', $result);
    // }
    
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
        
 
   
}