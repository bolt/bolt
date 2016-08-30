<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Config;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Validation\Validator;
use Bolt\Controller;
use PHPUnit_Extension_FunctionMocker;

/**
 * Abstract validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractValidationTest extends \PHPUnit_Framework_TestCase
{
    /** @var Controller\Exception */
    protected $extensionController;
    /** @var Validator */
    protected $validator;
    /** @var Config */
    protected $config;
    /** @var ResourceManager */
    protected $resourceManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $_filesystem;
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $_validation;

    public function setUp()
    {
        $this->_filesystem = PHPUnit_Extension_FunctionMocker::start($this, 'Symfony\Component\Filesystem')
            ->mockFunction('file_exists')
            ->mockFunction('is_dir')
            ->mockFunction('is_readable')
            ->mockFunction('is_writable')
            ->mockFunction('rmdir')
            ->mockFunction('touch')
            ->mockFunction('unlink')
            ->getMock()
        ;

        $this->_validation = PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Configuration\Validation')
            ->mockFunction('extension_loaded')
            ->mockFunction('file_exists')
            ->mockFunction('get_magic_quotes_gpc')
            ->mockFunction('ini_get')
            ->mockFunction('is_dir')
            ->mockFunction('is_readable')
            ->mockFunction('is_writable')
            ->mockFunction('rmdir')
            ->mockFunction('touch')
            ->mockFunction('unlink')
            ->getMock()
        ;

        $this->extensionController = $this->prophesize(Controller\Exception::class);
        $this->config = $this->prophesize(Config::class);
        $this->resourceManager = $this->prophesize(ResourceManager::class);

        $this->validator = new Validator(
            $this->extensionController->reveal(),
            $this->config->reveal(),
            $this->resourceManager->reveal()
        );
    }

    public function tearDown()
    {
        PHPUnit_Extension_FunctionMocker::tearDown();
    }
}
