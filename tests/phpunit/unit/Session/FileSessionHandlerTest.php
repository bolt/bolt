<?php
namespace Bolt\Tests\Session;

use Bolt\Session\FileSessionHandler;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Session/FileSessionHandler.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileSessionHandlerTest extends BoltUnitTest
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
        $this->sessionFile = $this->savePath . '/' . $this->sessionName . '.bolt_sess';
    }

    public function testConstructor()
    {
        $this->assertClassHasAttribute('savePath', 'Bolt\Session\FileSessionHandler');
        $this->assertClassHasAttribute('fs',       'Bolt\Session\FileSessionHandler');
        $this->assertClassHasAttribute('gcCalled', 'Bolt\Session\FileSessionHandler');

        $fsh = new FileSessionHandler($this->savePath);

        $this->assertObjectHasAttribute('savePath', $fsh);
        $this->assertObjectHasAttribute('fs',       $fsh);
        $this->assertObjectHasAttribute('gcCalled', $fsh);

        $this->assertAttributeEquals($this->savePath, 'savePath', $fsh);
    }

    public function testOpen()
    {
        $fsh = new FileSessionHandler($this->savePath);

        $result = $fsh->open($this->savePath, $this->sessionName);
        $this->assertTrue($result);
        $this->assertFileExists($this->sessionFile);
    }

    public function testClose()
    {
        $fsh = new FileSessionHandler($this->savePath);

        $result = $fsh->close();
        $this->assertTrue($result);
    }

    /**
     * @covers FileSessionHandler::write
     * @covers FileSessionHandler::read
     */
    public function testWriteRead()
    {
        $fsh = new FileSessionHandler($this->savePath);
        $fsh->open($this->savePath, $this->sessionName);

        $result = $fsh->write($this->sessionName, 'kittens');
        $this->assertTrue($result);

        $result = $fsh->read($this->sessionName);
        $this->assertSame('kittens', $result);
        $this->assertStringEqualsFile($this->sessionFile, 'kittens');
    }

    public function testDestroy()
    {
        $fsh = new FileSessionHandler($this->savePath);
        $fsh->open($this->savePath, $this->sessionName);

        $fsh->write($this->sessionName, 'kittens');
        $this->assertFileExists($this->sessionFile);

        $result = $fsh->destroy($this->sessionName);
        $this->assertTrue($result);
        $this->assertFileNotExists($this->sessionName);
    }

    public function testGc()
    {
        $fsh = new FileSessionHandler($this->savePath);
        $fsh->open($this->savePath, $this->sessionName);

        $fsh->write($this->sessionName, 'kittens');
        $this->assertFileExists($this->sessionFile);

        $result = $fsh->gc('yesterday');
        $this->assertNull($result);
        $this->assertFileNotExists($this->sessionName);
    }
}
