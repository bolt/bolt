<?php
namespace Bolt\Tests\Session;

use Bolt\Session\Handler\FileHandler;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Session/Handler/FileHandler.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileHandlerTest extends BoltUnitTest
{
    /** @var string */
    protected $savePath;
    /** @var string */
    protected $sessionName;
    /** @var string */
    protected $sessionFile;

    public function setUp()
    {
        $app = $this->getApp();
        $this->savePath = $app['resources']->getPath('cache');
        $this->sessionName = 'george';
        $this->sessionFile = $this->savePath . '/' . $this->sessionName . '.sess';
    }

    public function testConstructor()
    {
        $this->assertClassHasAttribute('savePath', 'Bolt\Session\Handler\FileHandler');
        $this->assertClassHasAttribute('fs',       'Bolt\Session\Handler\FileHandler');

        $fsh = new FileHandler($this->savePath);

        $this->assertObjectHasAttribute('savePath', $fsh);
        $this->assertObjectHasAttribute('fs',       $fsh);

        $this->assertAttributeEquals($this->savePath, 'savePath', $fsh);
    }

    public function testOpen()
    {
        $fsh = new FileHandler($this->savePath);

        $result = $fsh->open($this->savePath, $this->sessionName);
        $this->assertTrue($result);
    }

    public function testClose()
    {
        $fsh = new FileHandler($this->savePath);

        $result = $fsh->close();
        $this->assertTrue($result);
    }

    /**
     * @covers \Bolt\Session\Handler\FileHandler::write
     * @covers \Bolt\Session\Handler\FileHandler::read
     */
    public function testWriteRead()
    {
        $fsh = new FileHandler($this->savePath);
        $fsh->open($this->savePath, $this->sessionName);

        $result = $fsh->write($this->sessionName, 'kittens');
        $this->assertTrue($result);

        $result = $fsh->read($this->sessionName);
        $this->assertSame('kittens', $result);
        $this->assertStringEqualsFile($this->sessionFile, 'kittens');
    }

    public function testDestroy()
    {
        $fsh = new FileHandler($this->savePath);
        $fsh->open($this->savePath, $this->sessionName);

        $fsh->write($this->sessionName, 'kittens');
        $this->assertFileExists($this->sessionFile);

        $result = $fsh->destroy($this->sessionName);
        $this->assertTrue($result);
        $this->assertFileNotExists($this->sessionName);
    }

    public function testGc()
    {
        $fsh = new FileHandler($this->savePath);
        $fsh->open($this->savePath, $this->sessionName);

        $fsh->write($this->sessionName, 'kittens');
        $this->assertFileExists($this->sessionFile);

        sleep(1);
        $result = $fsh->gc(1);
        $this->assertTrue($result);
        $this->assertFileNotExists($this->sessionName);
    }
}
