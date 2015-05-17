<?php
namespace Bolt\Tests;

use Bolt\Application;
use Bolt\Configuration as Config;
use Bolt\Configuration\Standard;
use Bolt\Storage;
use Bolt\Tests\Mocks\LoripsumMock;
use Bolt\Twig\Handler\AdminHandler;
use Bolt\Twig\Handler\ArrayHandler;
use Bolt\Twig\Handler\HtmlHandler;
use Bolt\Twig\Handler\ImageHandler;
use Bolt\Twig\Handler\RecordHandler;
use Bolt\Twig\Handler\TextHandler;
use Bolt\Twig\Handler\UserHandler;
use Bolt\Twig\Handler\UtilsHandler;
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
            copy(PHPUNIT_ROOT . '/resources/db/bolt.db', TEST_ROOT . '/bolt.db');
        }
    }

    protected function getApp()
    {
        $bolt = $this->makeApp();
        $bolt->initialize();
        $bolt->boot();

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
        $bolt['deprecated.php'] = version_compare(PHP_VERSION, '5.4.0', '<');
        $bolt['debug'] = false;
        $bolt['config']->set(
            'general/database',
            array(
                'driver' => 'pdo_sqlite',
                'prefix' => 'bolt_',
                'user'   => 'test',
                'path'   => TEST_ROOT . '/bolt.db'
            )
        );
        $bolt['config']->set('general/canonical', 'bolt.dev');

        $bolt['session'] = $sessionMock;
        $bolt['resources']->setPath('files', PHPUNIT_ROOT . '/resources/files');
        $bolt['slugify'] = Slugify::create();

        return $bolt;
    }

    protected function rmdir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
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
        rmdir($dir);
    }

    protected function addDefaultUser(Application $app)
    {
        // Check if default user exists before adding
        $existingUser = $app['users']->getUser('admin');
        if (false !== $existingUser) {
            return $existingUser;
        }

        $user = array(
            'username'    => 'admin',
            'password'    => 'password',
            'email'       => 'admin@example.com',
            'displayname' => 'Admin',
            'roles'       => array('admin'),
        );

        $app['users']->saveUser(array_merge($app['users']->getEmptyUser(), $user));

        return $user;
    }

    protected function addNewUser($app, $username, $displayname, $role)
    {
        $user = array(
            'username'    => $username,
            'password'    => 'password',
            'email'       => $username.'@example.com',
            'displayname' => $displayname,
            'roles'       => array($role),
        );

        $app['users']->saveUser(array_merge($app['users']->getEmptyUser(), $user));
        $app['users']->users = array();
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
        $users = $this->getMock('Bolt\Users', array('isValidSession', 'isAllowed', 'isEnabled'), array($app));
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

    protected function getTwigHandlers($app)
    {
        return new \Pimple(array(
            'admin'  => $app->share(function () use ($app) { return new AdminHandler($app); }),
            'array'  => $app->share(function () use ($app) { return new ArrayHandler($app); }),
            'html'   => $app->share(function () use ($app) { return new HtmlHandler($app); }),
            'image'  => $app->share(function () use ($app) { return new ImageHandler($app); }),
            'record' => $app->share(function () use ($app) { return new RecordHandler($app); }),
            'text'   => $app->share(function () use ($app) { return new TextHandler($app); }),
            'user'   => $app->share(function () use ($app) { return new UserHandler($app); }),
            'utils'  => $app->share(function () use ($app) { return new UtilsHandler($app); }),
        ));
    }

    protected function removeCSRF($app)
    {
        // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('form'));
        $csrf->expects($this->any())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));

        $csrf->expects($this->any())
            ->method('generateCsrfToken')
            ->will($this->returnValue('xyz'));

        $app['form.csrf_provider'] = $csrf;
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('taxonomy/categories/options', array('news'));
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(array('showcases', 'pages'));
    }
}
