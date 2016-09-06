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
                'message' => 'src error',
            ],
            'extensions' => [
                'type'    => E_ERROR,
                'file'    => PHPUNIT_WEBROOT . '/extensions',
                'line'    => 1,
                'message' => 'extension error',
            ],
            'extension' => [
                'type'    => E_ERROR,
                'file'    => PHPUNIT_WEBROOT . '/extensions/vendor/gawain/clippy',
                'line'    => 1,
                'message' => 'extension error',
            ],
            'vendor' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT . '/vendor',
                'line'    => 1,
                'message' => 'vendor error',
            ],
            'unknown' => [
                'type'    => E_ERROR,
                'file'    => TEST_ROOT,
                'line'    => 1,
                'message' => 'unknown error',
            ],
        ];
    }

    protected function getApp($boot = true)
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

        try {
            $check->doChecks();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/Bolt requires 'Magic Quotes' to be off/", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testSafeModeException()
    {
        $check = $this->getCleanChecker();
        $check->safeMode = true;

        try {
            $check->doChecks();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/Bolt requires 'Safe mode' to be off/", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testApacheChecks()
    {
        $check = $this->getCleanChecker();
        $check->isApache = true;

        try {
            $check->doChecks();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/.htaccess doesn't exist. Make sure it's present and readable to the user that the webserver is using./", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp('/MySQL was selected as the database type, but the driver does not exist or is not loaded/', $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp('/PostgreSQL was selected as the database type, but the driver does not exist or is not loaded/', $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testPlatformFailsSqlite()
    {
        $check = $this->getMockedChecker('mockSqlite');
        $check->sqliteLoaded = false;

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp('/SQLite was selected as the database type, but the driver does not exist or is not loaded/', $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testPlatformUnsupported()
    {
        $check = $this->getMockedChecker('mockUnsupportedPlatform');

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp('/was selected as the database type, but it is not supported/', $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/The database file test\/bolt.db is not writable. Make sure it's present and writable to the user that the webserver is using./", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/The database folder test does not exist. Make sure it's present and writable to the user that the webserver is using./", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/The database folder test is not writable. Make sure it's present and writable to the user that the webserver is using./", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testDbFailsAsRootWithoutPassword()
    {
        $check = $this->getMockedChecker('mockRoot');

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/There is no password set for the database connection, and you're using user 'root'. That must surely be a mistake, right\? Bolt will stubbornly refuse to run until you've set a password for 'root'./", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testEmptyDb()
    {
        $check = $this->getMockedChecker('mockEmptyDb');

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp('/There is no databasename set for your database/', $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testEmptyDbUser()
    {
        $check = $this->getMockedChecker('mockEmptyUser');

        try {
            $check->doDatabaseCheck();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp('/There is no username set for your database/', $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testCoreFatalErrorCatch()
    {
        $this->php2
            ->expects($this->once())
            ->method('error_get_last')
            ->will($this->returnValue($this->errorResponses['core']));

        $this->expectOutputRegex('/PHP Fatal error: Bolt core/');
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
        $this->expectOutputRegex('/PHP Fatal error: Vendor library/');
        LowlevelException::catchFatalErrors($this->getApp(), false);
    }

    public function testExtFatalErrorCatch()
    {
        $this->php2
            ->expects($this->once())
            ->method('error_get_last')
            ->will($this->returnValue($this->errorResponses['extension']));

        $this->expectOutputRegex('/PHP Fatal error: Bolt extensions/');
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

        $this->expectOutputRegex('/PHP Fatal error: Bolt generic/');
        LowlevelException::catchFatalErrors($this->getApp(), false);
    }

    public function testAssertWritableDir()
    {
        $badDir = '/path/to/nowhere';
        $check = $this->getCleanChecker();

        try {
            $check->assertWritableDir($badDir);
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/The folder \/path\/to\/nowhere doesn't exist. Make sure it is present and writable to the user that the webserver is using./", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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

        try {
            $check->doChecks();
            $this->fail('Bolt\Exception\LowlevelException not thrown');
        } catch (LowlevelException $e) {
            $this->assertRegExp("/Couldn't read config.yml/", $e->getMessage());
            $this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
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
