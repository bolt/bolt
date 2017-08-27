<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * Sqlite database validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseSqliteTest extends AbstractValidationTest
{
    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\SqlitePathException
     */
    public function testSqliteExtensionNotLoaded()
    {
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => PHPUNIT_WEBROOT . '/app/database/bolt.db',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }

    public function testSqliteValidInMemoryParameter()
    {
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
        $this->addToAssertionCount(1);
    }

    public function testSqliteFileExistsWritable()
    {
        $file = 'app/database/bolt.db';
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);

        $this->_filesystem
            ->expects($this->once())
            ->method('file_exists')
            ->with($file)
            ->will($this->returnValue(true))
        ;

        $this->_filesystem
            ->expects($this->once())
            ->method('touch')
            ->will($this->returnValue(true))
            ->with($file)
        ;

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;
        $this->getDatabaseValidator()->check();
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\SqlitePathException
     */
    public function testSqliteFileExistsNotWritable()
    {
        $file = 'app/database/bolt.db';
        $dir = dirname($file);
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);

        $this->_filesystem
            ->expects($this->at(0))
            ->method('file_exists')
            ->with($file)
            ->will($this->returnValue(true))
        ;
        $this->_filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($file)
        ;

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->getDatabaseValidator()->check();
    }

    public function testSqliteFileNotExistsDirectoryWritable()
    {
        $file = 'app/database/bolt.db';
        $dir = dirname($file);
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);

        $this->_filesystem
            ->expects($this->at(0))
            ->method('file_exists')
            ->with($file)
            ->will($this->returnValue(false))
        ;

        $this->_filesystem
            ->expects($this->at(1))
            ->method('file_exists')
            ->with($dir)
            ->will($this->returnValue(true))
        ;

        $this->_filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($dir)
            ->will($this->returnValue(true))
        ;

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->getDatabaseValidator()->check();
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\SqlitePathException
     */
    public function testSqliteFileNotExistsDirectoryNotWritable()
    {
        $file = 'app/database/bolt.db';
        $dir = dirname($file);
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);

        $this->_filesystem
            ->expects($this->at(0))
            ->method('file_exists')
            ->with($file)
            ->will($this->returnValue(false))
        ;
        $this->_filesystem
            ->expects($this->at(1))
            ->method('file_exists')
            ->with($dir)
            ->will($this->returnValue(true))
        ;

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->getDatabaseValidator()->check();
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\SqlitePathException
     */
    public function testSqliteFileNotExistsDirectoryWritableCacheReset()
    {
        $file = 'app/database/bolt.db';
        $dir = dirname($file);
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->config->initialize()->shouldBeCalled();
        $this->pathResolver->resolve('%cache%/config-cache.json')->shouldBeCalled();

        $this->_filesystem
            ->expects($this->at(0))
            ->method('file_exists')
            ->with($file)
            ->will($this->returnValue(false))
        ;

        $this->_filesystem
            ->expects($this->at(1))
            ->method('file_exists')
            ->with($dir)
            ->will($this->returnValue(false))
        ;

        $this->_filesystem
            ->expects($this->at(2))
            ->method('file_exists')
            ->will($this->returnValue(true))
        ;

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->getDatabaseValidator()->check();
    }
}
