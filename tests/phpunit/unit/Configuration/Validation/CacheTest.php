<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Configuration\Validation\Validator;

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
        $this->extensionController->systemCheck(Validator::CHECK_CACHE)->shouldNotBeCalled();

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

        $this->validator->check(Validator::CHECK_CACHE);
    }

    public function testCacheDirectoryIsNotDirectory()
    {
        $this->extensionController->systemCheck(Validator::CHECK_CACHE, [], ['path' => null])->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('is_dir')
            ->will($this->returnValue(false))
        ;
        $this->_validation
            ->expects($this->never())
            ->method('is_writable')
        ;

        $this->validator->check(Validator::CHECK_CACHE);
    }

    public function testCacheDirectoryNotWritable()
    {
        $this->extensionController->systemCheck(Validator::CHECK_CACHE, [], ['path' => null])->shouldBeCalled();

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

        $this->validator->check(Validator::CHECK_CACHE);
    }
}
