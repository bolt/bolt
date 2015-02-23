<?php
namespace Bolt\Tests;

use Bolt\Application;
use Bolt\Configuration as Config;
use Bolt\Configuration\Standard;
use Cocur\Slugify\Slugify;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

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
        if (is_readable(TEST_ROOT . '/bolt.db')) {
            unlink(TEST_ROOT . '/bolt.db');
            copy(TEST_ROOT . '/tests/resources/db/bolt.db', TEST_ROOT . '/bolt.db');
        }
    }

    protected function getApp()
    {
        $bolt = $this->makeApp();
        $bolt->initialize();

        return $bolt;
    }

    protected function makeApp()
    {
        $sessionMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
        ->setMethods(array('clear'))
        ->setConstructorArgs(array(new MockFileSessionStorage()))
        ->getMock();

        $config = new Standard(TEST_ROOT);
        $config->verify();

        $bolt = new Application(array('resources' => $config));
        $bolt['config']->set(
            'general/database',
            array(
                'driver' => 'pdo_sqlite',
                'prefix' => 'bolt_',
                'user' => 'test',
                'path' => TEST_ROOT . '/bolt.db'
            )
        );
        $bolt['session'] = $sessionMock;
        $bolt['resources']->setPath('files', TEST_ROOT . '/tests/resources/files');
        $bolt['slugify'] = Slugify::create();

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

    protected function addDefaultUser(Application $app)
    {
        $user = $app['users']->getEmptyUser();
        $user['roles'] = array('admin');
        $user['username'] = 'admin';
        $user['password'] = 'password';
        $user['email'] = 'test@example.com';
        $user['displayname'] = 'Admin';
        $app['users']->saveUser($user);
    }

    protected function getMockTwig()
    {
        $twig = $this->getMock('Twig_Environment', array('render', 'fetchCachedRequest'));
        $twig->expects($this->any())
            ->method('fetchCachedRequest')
            ->will($this->returnValue(false));

        return $twig;
    }

    protected function checkTwigForTemplate($app, $testTemplate)
    {
        $twig = $this->getMockTwig();

        $twig->expects($this->any())
            ->method('render')
            ->with($this->equalTo($testTemplate))
            ->will($this->returnValue(new Response()));

        $app['render'] = $twig;
    }

    protected function allowLogin($app)
    {
        $this->addDefaultUser($app);
        $users = $this->getMock('Bolt\Users', array('isValidSession','isAllowed', 'isEnabled'), array($app));
        $users->expects($this->any())
            ->method('isValidSession')
            ->will($this->returnValue(true));

        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));

        $users->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $app['users'] = $users;
    }
}
