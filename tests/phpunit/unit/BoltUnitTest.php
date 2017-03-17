<?php

namespace Bolt\Tests;

use Bolt\AccessControl\AccessChecker;
use Bolt\AccessControl\Login;
use Bolt\AccessControl\Permissions;
use Bolt\AccessControl\Token;
use Bolt\Application;
use Bolt\Configuration as Config;
use Bolt\Legacy\Storage;
use Bolt\Logger\FlashLogger;
use Bolt\Logger\Manager;
use Bolt\Storage\Entity;
use Bolt\Tests\Mocks\LoripsumMock;
use Bolt\Users;
use Doctrine\Common\Cache\VoidCache;
use GuzzleHttp\Client;
use Monolog\Logger;
use PHPUnit_Framework_MockObject_MockObject;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Abstract Class that other unit tests can extend, provides generic methods for Bolt tests.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
abstract class BoltUnitTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    protected function resetDb()
    {
        // Make sure we wipe the db file to start with a clean one
        if (is_readable(PHPUNIT_WEBROOT . '/app/database/bolt.db')) {
            unlink(PHPUNIT_WEBROOT . '/app/database/bolt.db');
            copy(PHPUNIT_ROOT . '/resources/db/bolt.db', PHPUNIT_WEBROOT . '/app/database/bolt.db');
        }
    }

    protected function resetConfig()
    {
        $configFiles = [
            'config.yml',
            'contenttypes.yml',
            'menu.yml',
            'permissions.yml',
            'routing.yml',
            'taxonomy.yml',
        ];
        foreach ($configFiles as $configFile) {
            // Make sure we wipe the db file to start with a clean one
            if (is_readable(PHPUNIT_WEBROOT . '/app/config/' . $configFile)) {
                unlink(PHPUNIT_WEBROOT . '/app/config/' . $configFile);
            }
        }
        if (is_readable(PHPUNIT_WEBROOT . '/app/cache/config-cache.json')) {
            unlink(PHPUNIT_WEBROOT . '/app/cache/config-cache.json');
        }
    }

    /**
     * @param bool $boot
     *
     * @return Application
     */
    protected function getApp($boot = true)
    {
        if (!$this->app) {
            $this->app = $this->makeApp();
            $this->app->initialize();

            $verifier = new Config\Validation\Validator(
                $this->app['config'],
                $this->app['path_resolver'],
                $this->app['logger.flash']
            );
            $verifier->checks();

            if ($boot) {
                $this->app->boot();
                $this->app->flush();
            }
        }

        return $this->app;
    }

    /**
     * @return Application
     */
    protected function makeApp()
    {
        $app = new Application();
        $app['path_resolver.root'] = PHPUNIT_WEBROOT;
        $app['path_resolver.paths'] = ['web' => '.'];
        $app['debug'] = false;

        $app['config']->set(
            'general/database',
            [
                'driver'       => 'pdo_sqlite',
                'prefix'       => 'bolt_',
                'user'         => 'test',
                'path'         => PHPUNIT_WEBROOT . '/app/database/bolt.db',
                'wrapperClass' => '\Bolt\Storage\Database\Connection',
            ]
        );

        return $app;
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

        $user = [
            'username'    => 'admin',
            'password'    => 'password',
            'email'       => 'admin@example.com',
            'displayname' => 'Admin',
            'roles'       => ['admin'],
            'enabled'     => true,
        ];

        $app['users']->saveUser(array_merge($app['users']->getEmptyUser(), $user));

        return $user;
    }

    protected function addNewUser($app, $username, $displayname, $role, $enabled = true)
    {
        $user = [
            'username'    => $username,
            'password'    => 'password',
            'email'       => $username . '@example.com',
            'displayname' => $displayname,
            'roles'       => [$role],
            'enabled'     => $enabled,
        ];

        $app['users']->saveUser(array_merge($app['users']->getEmptyUser(), $user));
        $app['users']->users = [];
    }

    protected function allowLogin($app)
    {
        $this->addDefaultUser($app);
        $users = $this->getMockUsers(['isEnabled']);
        $users->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        $permissions = $this->getMockPermissions(['isAllowed']);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $auth = $this->getMockAccessChecker($app);
        $auth->expects($this->any())
            ->method('isValidSession')
            ->will($this->returnValue(true));

        $app['access_control'] = $auth;
    }

    protected function removeCSRF($app)
    {
        // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMockCsrfTokenManager(['isTokenValid', 'getToken']);
        $csrf->expects($this->any())
            ->method('isTokenValid')
            ->will($this->returnValue(true));

        $csrf->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue('xyz'));

        $app['form.csrf_provider'] = $csrf;
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('taxonomy/categories/options', ['news']);
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(['showcases', 'pages']);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    protected function setService($key, $value)
    {
        $this->getApp()->offsetSet($key, $value);
    }

    protected function getService($key)
    {
        return $this->getApp()->offsetGet($key);
    }

    protected function setSessionUser(Entity\Users $userEntity)
    {
        $tokenEntity = new Entity\Authtoken();
        $tokenEntity->setToken('testtoken');
        $authToken = new Token\Token($userEntity, $tokenEntity);

        $this->getService('session')->set('authentication', $authToken);
    }

    /*
     * MOCKS
     */

    /**
     * @param \Silex\Application $app
     * @param array              $methods Defaults to ['isValidSession']
     *
     * @return \Bolt\AccessControl\AccessChecker|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockAccessChecker($app, $methods = ['isValidSession'])
    {
        $parameters = [
            $app['storage.lazy'],
            $app['request_stack'],
            $app['session'],
            $app['dispatcher'],
            $app['logger.flash'],
            $app['logger.system'],
            $app['permissions'],
            $app['randomgenerator'],
            $app['access_control.cookie.options'],
        ];
        $accessCheckerMock =  $this->getMockBuilder(AccessChecker::class)
            ->setMethods($methods)
            ->setConstructorArgs($parameters)
            ->getMock()
        ;

        return $accessCheckerMock;
    }

    /**
     * @return VoidCache|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockCache()
    {
        return $this->getMockBuilder(VoidCache::class)
            ->setMethods(['flushAll'])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return CsrfTokenManager|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockCsrfTokenManager($methods = ['isTokenValid'])
    {
        return $this->getMockBuilder(CsrfTokenManager::class)
            ->setMethods($methods)
            ->setConstructorArgs([null, new SessionTokenStorage(new Session(new MockArraySessionStorage()))])
            ->getMock()
        ;
    }

    /**
     * @return Client|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockGuzzleClient()
    {
        return $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return FlashLogger|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockFlashLogger($methods = ['danger', 'error', 'success'])
    {
        return $this->getMockBuilder(FlashLogger::class)
            ->setMethods($methods)
            ->getMock()
        ;
    }

    /**
     * @param array $methods Defaults to ['login']
     *
     * @return PHPUnit_Framework_MockObject_MockObject A mocked \Bolt\AccessControl\Login
     */
    protected function getMockLogin($methods = ['login'])
    {
        return $this->getMockBuilder(Login::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Manager|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLoggerManager($methods = ['clear', 'error', 'info', 'trim'])
    {
        $app = $this->getApp();
        $changeRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogSystem');

        return $this->getMockBuilder(Manager::class)
            ->setMethods($methods)
            ->setConstructorArgs([$app, $changeRepository, $systemRepository])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Logger|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockMonolog($methods = ['alert', 'clear', 'debug', 'error', 'info'])
    {
        return $this->getMockBuilder(Logger::class)
            ->setMethods($methods)
            ->setConstructorArgs(['testlogger'])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Permissions|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockPermissions($methods = ['isAllowed', 'isAllowedToManipulate'])
    {
        return $this->getMockBuilder(Permissions::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return PHPUnit_Framework_MockObject_MockObject|Swift_Mailer
     */
    protected function getMockSwiftMailer($methods = ['send'])
    {
        return $this->getMockBuilder(Swift_Mailer::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->app['swiftmailer.transport']])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Storage|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockStorage($methods = ['getContent', 'getContentType', 'getTaxonomyType'])
    {
        return $this->getMockBuilder(Storage::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Users|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockUsers($methods = ['getUsers', 'isAllowed'])
    {
        return $this->getMockBuilder(Users::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
        ;
    }

    /**
     * @deprecated Remove in v4 as PHPUnit 5 includes this.
     */
    protected function createMock($originalClassName)
    {
        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock()
        ;
    }
}
