<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Config;
use Bolt\Configuration\PathResolver;
use Bolt\Configuration\Validation;
use Bolt\Configuration\Validation\Validator;
use Bolt\Logger\FlashLogger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Extension_FunctionMocker as FunctionMocker;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Abstract validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractValidationTest extends TestCase
{
    /** @var Validator */
    protected $validator;
    /** @var Config */
    protected $config;
    /** @var PathResolver */
    protected $pathResolver;
    /** @var FlashLogger */
    protected $flashLogger;

    /** @var MockObject */
    protected $_filesystem;
    /** @var MockObject */
    protected $_validation;

    public function setUp()
    {
        $this->_filesystem = FunctionMocker::start($this, 'Symfony\Component\Filesystem')
            ->mockFunction('file_exists')
            ->mockFunction('is_dir')
            ->mockFunction('is_readable')
            ->mockFunction('is_writable')
            ->mockFunction('rmdir')
            ->mockFunction('touch')
            ->mockFunction('unlink')
            ->getMock()
        ;

        $this->_validation = FunctionMocker::start($this, 'Bolt\Configuration\Validation')
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
        $this->pathResolver = $this->prophesize(PathResolver::class);
        $this->flashLogger = $this->prophesize(FlashLogger::class);

        $this->validator = new Validator(
            $this->config->reveal(),
            $this->pathResolver->reveal(),
            $this->flashLogger->reveal()
        );
    }

    public function tearDown()
    {
        FunctionMocker::tearDown();
    }

    /**
     * @return Validation\Database
     */
    protected function getDatabaseValidator()
    {
        $validator = new Validation\Database();
        $validator->setConfig($this->config->reveal());
        $validator->setPathResolver($this->pathResolver->reveal());

        return $validator;
    }
}
