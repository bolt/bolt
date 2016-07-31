<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * Cache validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CacheTest extends AbstractValidationTest
{
    public function testCacheDirectoryIsValid()
    {
        $this->extensionController->systemCheck('cache-dir')->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('is_dir')
            ->will($this->returnValue(true))
        ;
        $this->_validation
            ->expects($this->once())
            ->method('is_writable')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('cache');
    }

    public function testCacheDirectoryIsNotDirectory()
    {
        $this->extensionController->systemCheck('cache-dir', [], ['path' => null])->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('is_dir')
            ->will($this->returnValue(false))
        ;
        $this->_validation
            ->expects($this->never())
            ->method('is_writable')
        ;

        $this->validator->check('cache');
    }

    public function testCacheDirectoryNotWritable()
    {
        $this->extensionController->systemCheck('cache-dir', [], ['path' => null])->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('is_dir')
            ->will($this->returnValue(true))
        ;
        $this->_validation
            ->expects($this->once())
            ->method('is_writable')
            ->will($this->returnValue(false))
        ;

        $this->validator->check('cache');
    }
}
