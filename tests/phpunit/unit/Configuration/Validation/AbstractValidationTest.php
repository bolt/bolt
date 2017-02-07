<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Config;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Validation;
use Bolt\Configuration\Validation\Validator;
use Bolt\Logger\FlashLogger;
use PHPUnit_Extension_FunctionMocker;
use PHPUnit_Framework_TestCase;

/**
 * Abstract validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractValidationTest extends PHPUnit_Framework_TestCase
{
    /** @var Validator */
    protected $validator;
    /** @var Config */
    protected $config;
    /** @var ResourceManager */
    protected $resourceManager;
    /** @var FlashLogger */
    protected $flashLogger;

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

        $this->config = $this->prophesize(Config::class);
        $this->resourceManager = $this->prophesize(ResourceManager::class);
        $this->flashLogger = $this->prophesize(FlashLogger::class);

        $this->validator = new Validator(
            $this->config->reveal(),
            $this->resourceManager->reveal(),
            $this->flashLogger->reveal()
        );
    }

    public function tearDown()
    {
        PHPUnit_Extension_FunctionMocker::tearDown();
    }

    /**
     * @return Validation\Database
     */
    protected function getDatabaseValidator()
    {
        $validator = new Validation\Database();
        $validator->setConfig($this->config->reveal());
        $validator->setResourceManager($this->resourceManager->reveal());

        return $validator;
    }
}
