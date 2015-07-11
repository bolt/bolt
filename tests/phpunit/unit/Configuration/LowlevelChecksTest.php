<?php
namespace Bolt\Tests\Configuration;

use Bolt\Configuration\LowlevelChecks;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Standard;
use Bolt\Exception\LowlevelException;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Configuration/LowlevelChecks.php.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 * @runTestsInSeparateProcesses
 */
class LowlevelChecksTest extends BoltUnitTest
{
    protected $errorResponses = [];

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $php;

    public function setUp()
    {
        $this->php = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Configuration')
            ->mockFunction('is_readable')
            ->mockFunction('is_writable')
            ->mockFunction('file_exists')
            ->mockFunction('is_dir')
            ->mockFunction('copy')
            ->mockFunction('error_get_last')
            ->getMock();

        $this->php2 = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Exception')
            ->mockFunction('error_get_last')
            ->getMock();

        $this->errorResponses = [
            'core' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT . '/src',
                'line'    => 16,
                'message' => 'src error'
            ],
            'extensions' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT . '/extensions',
                'line'    => 1,
                'message' => 'extension error'
            ],
            'extension' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT . '/extensions/vendor/gawain/clippy',
                'line'    => 1,
                'message' => 'extension error'
            ],
            'vendor' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT . '/vendor',
                'line'    => 1,
                'message' => 'vendor error'
            ],
            'unknown' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT,
                'line'    => 1,
                'message' => 'unknown error'
            ]
        ];
    }

    protected function getApp()
    {
        $this->php
            ->expects($this->any())
            ->method('is_dir')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->any())
            ->method('file_exists')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->any())
            ->method('is_writable')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->any())
            ->method('is_readable')
            ->will($this->returnValue(true));

        return parent::getApp();
    }

    public function tearDown()
    {
        \PHPUnit_Extension_FunctionMocker::tearDown();
    }

    public function testDefaultChecks()
    {
        $config = new Standard(TEST_ROOT);
        $check = new LowlevelChecks($config);
    }

    public function testMagicQuoteException()
    {
        $check = $this->getCleanChecker();
        $check->magicQuotes = true;
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();
    }

    public function testSafeModeException()
    {
        $check = $this->getCleanChecker();
        $check->safeMode = true;
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();
    }

    public function testApacheChecks()
    {
        $check = $this->getCleanChecker();
        $check->isApache = true;
        $this->setExpectedException('Bolt\Exception\LowlevelException');
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
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();
    }

    public function testGoodMysql()
    {
        $check = $this->getMockedChecker('mockMysql');
        $check->mysqlLoaded = true;
        $check->doDatabaseCheck();
    }

    public function testGoodPostgres()
    {
        $check = $this->getMockedChecker('mockPostgres');
        $check->postgresLoaded = true;
        $check->doDatabaseCheck();
    }

    public function testGoodSqliteMem()
    {
        $check = $this->getMockedChecker('mockSqliteMem');
        $check->sqliteLoaded = true;
        $check->doDatabaseCheck();
    }

    public function testPlatformFailsPostgres()
    {
        $check = $this->getMockedChecker('mockPostgres');
        $check->postgresLoaded = false;
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();
    }

    public function testPlatformFailsSqlite()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = false;
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();
    }

    public function testPlatformUnsupported()
    {
        $check = $this->getMockedChecker('mockUnsupportedPlatform');
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/database type, but it is not supported/");
        $check->doDatabaseCheck();
    }

    public function testGoodSqliteFile()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = true;

        $this->php
            ->expects($this->once())
            ->method('file_exists')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->once())
            ->method('is_writable')
            ->will($this->returnValue(true));

        $check->doDatabaseCheck();
    }

    public function testGoodSqliteDir()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = true;

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->with('test/bolt.db')
            ->will($this->returnValue(false));
        $this->php
            ->expects($this->at(1))
            ->method('file_exists')
            ->with('test')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->once())
            ->method('is_writable')
            ->with('test')
            ->will($this->returnValue(true));

        $check->doDatabaseCheck();
    }

    public function testSqliteFileExistsAndNotWritable()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $this->php
            ->expects($this->once())
            ->method('file_exists')
            ->with('test/bolt.db')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->once())
            ->method('is_writable')
            ->with('test/bolt.db')
            ->will($this->returnValue(false));

        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/is not writable/");
        $check->doDatabaseCheck();
    }

    public function testSqliteNonexistentDir()
    {
        $check = $this->getMockedChecker('mockSqlite');

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->with('test/bolt.db')
            ->will($this->returnValue(false));
        $this->php
            ->expects($this->at(1))
            ->method('file_exists')
            ->with('test')
            ->will($this->returnValue(false));

        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/does not exist/");
        $check->doDatabaseCheck();
    }

    public function testSqliteUnwritableDir()
    {
        $check = $this->getMockedChecker('mockSqlite');

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->with('test/bolt.db')
            ->will($this->returnValue(false));
        $this->php
            ->expects($this->at(1))
            ->method('file_exists')
            ->with('test')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->once())
            ->method('is_writable')
            ->with('test')
            ->will($this->returnValue(false));

        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/is not writable/");
        $check->doDatabaseCheck();
    }

    public function testDbFailsAsRootWithoutPassword()
    {
        $check = $this->getMockedChecker('mockRoot');
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt will stubbornly refuse to run until you've set a password/");
        $check->doDatabaseCheck();
    }

    public function testEmptyDb()
    {
        $check = $this->getMockedChecker('mockEmptyDb');
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();
    }

    public function testEmptyDbUser()
    {
        $check = $this->getMockedChecker('mockEmptyUser');
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doDatabaseCheck();
    }

    public function testCoreFatalErrorCatch()
    {
        $this->php2
            ->expects($this->once())
            ->method('error_get_last')
            ->will($this->returnValue($this->errorResponses['core']));

        $this->expectOutputRegex("/PHP Fatal Error: Bolt Core/");
        LowlevelException::catchFatalErrors($this->getApp(), false);
    }

    public function testVendorFatalErrorCatch()
    {
        $app = ['resources' => new Standard(TEST_ROOT)];
        ResourceManager::$theApp = $app;

        $this->php2
            ->expects($this->once())
            ->method('error_get_last')
            ->will($this->returnValue($this->errorResponses['vendor']));

        $app = $this->getApp();
        $this->expectOutputRegex("/PHP Fatal Error: Vendor Library/");
        LowlevelException::catchFatalErrors($this->getApp(), false);
    }

    public function testExtFatalErrorCatch()
    {
        $app = ['resources' => new Standard(TEST_ROOT)];
        ResourceManager::$theApp = $app;

        $this->php2
            ->expects($this->once())
            ->method('error_get_last')
            ->will($this->returnValue($this->errorResponses['extension']));

        $this->expectOutputRegex("/PHP Fatal Error: Bolt Extensions/");
        LowlevelException::catchFatalErrors($this->getApp(), false);
    }

    public function testGeneralFatalErrorCatch()
    {
        $app = ['resources' => new Standard(TEST_ROOT)];
        ResourceManager::$theApp = $app;

        $this->php2
            ->expects($this->once())
            ->method('error_get_last')
            ->will($this->returnValue($this->errorResponses['unknown']));

        $this->expectOutputRegex("/PHP Fatal Error: Bolt Generic/");
        LowlevelException::catchFatalErrors($this->getApp(), false);
    }

    public function testAssertWritableDir()
    {
        $badDir = "/path/to/nowhere";
        $check = $this->getCleanChecker();
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->assertWritableDir($badDir);
    }

    public function testConfigFileAlreadyExistsSoIgnore()
    {
        $check = $this->getCleanChecker();
        $check->configChecks = ['config'];
        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->will($this->returnValue(true));
        $this->php
            ->expects($this->at(1))
            ->method('file_exists')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(true));

        $check->doChecks();
    }

    public function testConfigFileCreationErrors()
    {
        $check = $this->getCleanChecker();
        $check->configChecks = ['config'];

        $this->php
            ->expects($this->once())
            ->method('file_exists')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(false));

        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $check->doChecks();
    }

    // This helper provides a mocked checker object with the config values preset
    protected function getMockedChecker($mockMethod)
    {
        $check = $this->getCleanChecker();
        $mock = new Mock\Config();
        $mock->$mockMethod();
        $configObject = new \ArrayObject(['config' => $mock]);
        $check->config->app = $configObject;

        return $check;
    }

    protected function getCleanChecker()
    {
        $config = new Standard(TEST_ROOT);
        $check = new LowlevelChecks($config);
        $check->removeCheck('cache');
        $check->configChecks = [];

        return $check;
    }
}
