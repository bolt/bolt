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
    public function testSqliteExtensionNotLoaded()
    {
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('missing', 'SQLite', 'pdo_sqlite')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(false))
        ;

        $this->validator->check('database');
    }

    public function testSqliteValidInMemoryParameter()
    {
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('missing', 'SQLite', 'pdo_sqlite')->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('database');
    }

    public function testSqliteFileExistsWritable()
    {
        $file = 'app/database/bolt.db';
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databasePath('file', $file, 'is not writable')->shouldNotBeCalled();

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

        $this->validator->check('database');
    }

    public function testSqliteFileExistsNotWritable()
    {
        $file = 'app/database/bolt.db';
        $dir = dirname($file);
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databasePath('file', $file, 'is not writable')->shouldBeCalled();

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

        $this->validator->check('database');
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
        $this->extensionController->databasePath('folder', $dir, 'is not writable')->shouldNotBeCalled();

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

        $this->validator->check('database');
    }

    public function testSqliteFileNotExistsDirectoryNotWritable()
    {
        $file = 'app/database/bolt.db';
        $dir = dirname($file);
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'path'   => $file,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databasePath('folder', $dir, 'is not writable')->shouldBeCalled();

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

        $this->validator->check('database');
    }

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
        $this->resourceManager->getPath('cache/config-cache.json')->shouldBeCalled();

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

        $this->validator->check('database');
    }
}
