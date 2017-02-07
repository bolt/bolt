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
        $this->_validation
            ->expects($this->once())
            ->method('is_dir')
            ->willReturn(true)
        ;
        $this->_validation
            ->expects($this->once())
            ->method('is_writable')
            ->willReturn(true)
        ;

        $this->validator->check(Validator::CHECK_CACHE);
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\System\CacheValidationException
     */
    public function testCacheDirectoryIsNotDirectory()
    {
        $this->_validation
            ->expects($this->once())
            ->method('is_dir')
            ->willReturn(false)
        ;
        $this->_validation
            ->expects($this->never())
            ->method('is_writable')
        ;

        $this->validator->check(Validator::CHECK_CACHE);
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\System\CacheValidationException
     */
    public function testCacheDirectoryNotWritable()
    {
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
