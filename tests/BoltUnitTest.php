<?php
namespace Bolt\Tests;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Bolt\Configuration as Config;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Bolt\Configuration\ResourceManager;

/**
 * Abstract Class that other unit tests can extend, provides generic methods for Bolt tests.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


abstract class BoltUnitTest extends \PHPUnit_Framework_TestCase
{

    protected function resetDb()
    {
        // Make sure we wipe the db file to start with a clean one
        if(is_readable(TEST_ROOT.'/bolt.db')) {
            unlink(TEST_ROOT.'/bolt.db');
        }   
    }
    protected function getApp()
    {
        $bolt = $this->makeApp();
        $bolt->initialize();
        $bolt['integritychecker']->repairTables();
        return $bolt;
    }
    
    protected function makeApp()
    {
        $sessionMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
        ->setMethods(array('clear'))
        ->setConstructorArgs(array(new MockFileSessionStorage()))
        ->getMock();

        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );

        $bolt = new Application(array('resources' => $config));
        $bolt['config']->set(
            'general/database',
            array(
                'driver' => 'sqlite',
                'username' => 'test',
                'memory' => true,
                'path' => false
            )
        );
        $bolt['session'] = $sessionMock;
        $bolt['resources']->setPath('files', TEST_ROOT . '/tests/resources/files');
        return $bolt;
    }

    protected function rmdir($dir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }
    
    protected function addDefaultUser()
    {
        $app = $this->getApp();
        $user = $app['users']->getEmptyUser();
        $user['roles']=array('admin');
        $user['username'] = 'admin';
        $user['password'] = 'password';
        $user['email'] = 'test@example.com';
        $user['displayname'] = 'Admin';
        $app['users']->saveUser($user);
    }
}
