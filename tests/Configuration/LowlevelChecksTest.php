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
    
    public static $isWritable = null;
    public static $isReadable = null;
    public static $fileExists = null;


    
    public function testDefaultChecks()
    {
        $config = new Standard(TEST_ROOT);
        $check = new LowlevelChecks($config);        
        
    }
    
    public function testMagicQuoteException()
    {
        $check = $this->getCleanChecker(); 
        $check->magicQuotes = true;
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();      
        
    }
    
    public function testSafeModeException()
    {
        $check = $this->getCleanChecker(); 
        $check->safeMode = true;
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();      
        
    }
    
    public function testFallbackConfigFiles()
    {
        $config = new Standard(__DIR__);
        $check = new LowlevelChecks($config);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->removeCheck('cache');
        $check->doChecks();      
        
    }
    
    public function testApacheChecks()
    {
        $check = $this->getCleanChecker();
        $check->isApache = true;
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
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
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck(); 
    }
    
    public function testGoodMysql()
    {
        $check = $this->getMockedChecker('mockMysql'); 
        $check->doDatabaseCheck(); 
    }
    
    public function testGoodPostgres()
    {
        $check = $this->getMockedChecker('mockPostgres'); 
        $check->doDatabaseCheck(); 
    }
    
    public function testGoodSqliteMem()
    {
        $check = $this->getMockedChecker('mockSqliteMem'); 
        $check->doDatabaseCheck(); 
    }
    
    public function testPlatformFailsPostgres()
    {
        $check = $this->getMockedChecker('mockPostgres');
        $check->postgresLoaded = false;
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck(); 
    }
    
    public function testPlatformFailsSqlite()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = false;
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck(); 
    }
    
    public function testSqliteAttemptsFile()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = true;
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/exist or it is not writable/");
        $check->doDatabaseCheck(); 
    }
    
    public function testDBFailsAsRoot()
    {
        $check = $this->getMockedChecker('mockRoot');
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    
    public function testEmptyDb()
    {
        $check = $this->getMockedChecker('mockEmptyDb');
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    
    public function testBadDb()
    {
        $check = $this->getMockedChecker('mockBadDb');
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
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
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();      
    }
    
    public function testSqliteUnwritable()
    {
        $check = $this->getMockedChecker('mockSqlite');
        nativeFunctionExpects('is_writable', false);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
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
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->assertWritableDir($badDir);        
    }
    
    public function testUnwritableDir()
    {
        $check = $this->getMockedChecker('mockSqlite');
        nativeFunctionExpects('is_writable', false);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/present and writable/");
        $check->assertWritableDir(__DIR__);

    }
    
    public function testFileExistsAndNotWritable()
    {
        $check = $this->getMockedChecker('mockSqlite');
        nativeFunctionExpects('is_writable', array(true, false));
        nativeFunctionExpects('file_exists', true);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/present and writable/");
        $check->doDatabaseCheck();      

    }
    
    public function testConfigFileCreationErrors()
    {
        $check = $this->getCleanChecker();
        $check->configChecks = array('config');
        nativeFunctionExpects('file_exists', true);
        nativeFunctionExpects('is_readable', false);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();              
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

namespace Bolt\Configuration;

function is_writable($path)
{
    return mockNativeFunction('is_writable', func_get_args());
}

function is_readable($path)
{
    return mockNativeFunction('is_readable', func_get_args());
}

function file_exists()
{
    return mockNativeFunction('file_exists', func_get_args());
}

function is_dir()
{
    return mockNativeFunction('is_dir', func_get_args());
}


