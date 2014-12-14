<?php
namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Standard;
use Bolt\Configuration\LowlevelChecks;
use Bolt\Configuration\LowlevelException;
use Bolt\Configuration\ErrorSimulator;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Configuration/LowlevelChecks.php.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class LowlevelChecksTest extends BoltUnitTest
{

    
    public function testDefaultChecks()
    {
        $config = new Standard(TEST_ROOT);
        $check = new LowlevelChecks($config);        
        
    }
    
    public function testMagicQuoteException()
    {
        $check = $this->getCleanChecker(); 
        $check->magicQuotes = true;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();      
        
    }
    
    public function testSafeModeException()
    {
        $check = $this->getCleanChecker(); 
        $check->safeMode = true;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();      
        
    }
    
    public function testFallbackConfigFiles()
    {
        $config = new Standard(__DIR__);
        $check = new LowlevelChecks($config);
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->removeCheck('cache');
        $check->doChecks();      
        
    }
    
    public function testApacheChecks()
    {
        $check = $this->getCleanChecker();
        $check->isApache = true;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();      
        
    }
    
    public function testApacheCheckCanBeDisabled()
    {
        $check = $this->getCleanChecker();
        $check->isApache = true;
        $check->disableApacheChecks();
        $check->doChecks();      
        
    }
    
    public function testPlatformFailsMysql()
    {
        $check = $this->getMockedChecker('mockMysql');
        $check->mysqlLoaded = false;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck(); 
    }
    
    public function testPlatformFailsPostgres()
    {
        $check = $this->getMockedChecker('mockPostgres');
        $check->postgresLoaded = false;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck(); 
    }
    
    public function testPlatformFailsSqlite()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = false;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck(); 
    }
    
    public function testSqliteAttemptsFile()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = true;
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/exist or it is not writable/");
        $check->doDatabaseCheck(); 
    }
    
    public function testDBFailsAsRoot()
    {
        $check = $this->getMockedChecker('mockRoot');
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    
    public function testEmptyDb()
    {
        $check = $this->getMockedChecker('mockEmptyDb');
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    
    public function testBadDb()
    {
        $check = $this->getMockedChecker('mockBadDb');
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    
    public function testMissingDb()
    {
        // This should silently ignore the checks
        $check = $this->getMockedChecker('mockNoDriver');
        $check->doDatabaseCheck();      
    }
    
    public function testEmptyUser()
    {
        $check = $this->getMockedChecker('mockEmptyUser');
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    

    /**
    * @runInSeparateProcess
    */
    public function testFatalErrorCatch()
    {
        $app = $this->getApp();
        ErrorSimulator::simulateError($app, 'core');
        $this->expectOutputRegex("/Bolt Core - Fatal Error/");
        LowlevelException::catchFatalErrors($app);
        
        ErrorSimulator::simulateError($app, 'vendor');
        $this->expectOutputRegex("/Bolt Vendor Library - Fatal Error/");
        LowlevelException::catchFatalErrors($app);
        
        ErrorSimulator::simulateError($app, 'extensions');
        $this->expectOutputRegex("/Bolt Extensions - Fatal Error/");
        LowlevelException::catchFatalErrors($app);
        
        ErrorSimulator::simulateError($app, 'unknown');
        $this->expectOutputRegex("/PHP Fatal Error: Bolt Generic/");
        LowlevelException::catchFatalErrors($app);
        
    }
    
    public function testAssertWritableDir()
    {
        $badDir = "/path/to/nowhere";
        $check = $this->getCleanChecker();
        $this->setExpectedException(LowlevelException::class);
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->assertWritableDir($badDir);
    }
    
    
    // This helper provides a mocked checker object with the config values preset
    protected function getMockedChecker($mockMethod)
    {
        $check = $this->getCleanChecker();
        $mock = new Mock\Config();
        $mock->$mockMethod();
        $configObject = new \ArrayObject(array('config'=>$mock));
        $check->config->app = $configObject;
        return $check;
    }
    
    protected function getCleanChecker()
    {
        $config = new Standard(__DIR__);
        $check = new LowlevelChecks($config);
        $check->removeCheck('cache');
        $check->configChecks = array();
        return $check;
    }

   
}
