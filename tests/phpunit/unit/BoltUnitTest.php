<?php

namespace Bolt\Tests;

use Bolt;
use Bolt\AccessControl\AccessChecker;
use Bolt\AccessControl\Login;
use Bolt\AccessControl\Permissions;
use Bolt\AccessControl\Token;
use Bolt\Configuration as Config;
use Bolt\Legacy\Storage;
use Bolt\Logger\FlashLogger;
use Bolt\Logger\Manager;
use Bolt\Storage\Entity;
use Bolt\Tests\Mocks\ImageApiMock;
use Bolt\Tests\Mocks\LoripsumMock;
use Bolt\Users;
use Doctrine\Common\Cache\VoidCache;
use GuzzleHttp\Client;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Silex\Application;
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
abstract class BoltUnitTest extends TestCase
{
    /** @var \Silex\Application|\Bolt\Application */
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
     * @return \Silex\Application
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
     * @return \Silex\Application
     */
    protected function makeApp()
    {
        $app = new Bolt\Application();
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
        if ($existingUser !== false) {
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

        /** @var \Bolt\Users $users */
        $users = $app['users'];
        $users->saveUser(array_merge($users->getEmptyUser(), $user));
        $users->users = [];
    }

    protected function allowLogin($app)
    {
        $this->addDefaultUser($app);
        $users = $this->getMockUsers(['isEnabled']);
        $users->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $permissions = $this->getMockPermissions(['isAllowed']);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $auth = $this->getMockAccessChecker($app);
        $auth->expects($this->any())
            ->method('isValidSession')
            ->will($this->returnValue(true));

        $this->setService('access_control', $auth);
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

        $this->setService('form.csrf_provider', $csrf);
    }

    /**
     * @param array $contentTypes
     * @param array $categories
     * @param int   $count
     */
    protected function addSomeContent($contentTypes = null, $categories = null, $count = null)
    {
        $contentTypes = $contentTypes ?: ['showcases', 'pages', 'homepage'];
        $categories = $categories ?: ['news'];
        $count = $count ?: 5;

        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('taxonomy/categories/options', $categories);
        $this->setService('prefill', new LoripsumMock());
        $this->setService('prefill.image', new ImageApiMock());

        $builder = $app['prefill.builder'];
        $builder->build($contentTypes, $count);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    protected function setService($key, $value)
    {
        // In Pimple v3+ you can't re-set a container value,
        // this just keeps us working forward with tests.
        $this->getApp()->offsetUnset($key);
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
     * @return \Bolt\AccessControl\AccessChecker|MockObject
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
        $accessCheckerMock = $this->getMockBuilder(AccessChecker::class)
            ->setMethods($methods)
            ->setConstructorArgs($parameters)
            ->getMock()
        ;

        return $accessCheckerMock;
    }

    /**
     * @return VoidCache|MockObject
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
     * @return CsrfTokenManager|MockObject
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
     * @return Client|MockObject
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
     * @return FlashLogger|MockObject
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
     * @return MockObject A mocked \Bolt\AccessControl\Login
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
     * @return Manager|MockObject
     */
    protected function getMockLoggerManager($methods = ['clear', 'error', 'info', 'trim'])
    {
        $app = $this->getApp();
        $changeRepository = $this->getService('storage')->getRepository(Entity\LogChange::class);
        $systemRepository = $this->getService('storage')->getRepository(Entity\LogSystem::class);

        return $this->getMockBuilder(Manager::class)
            ->setMethods($methods)
            ->setConstructorArgs([$app, $changeRepository, $systemRepository])
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Logger|MockObject
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
     * @return Permissions|MockObject
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
     * @return MockObject|Swift_Mailer
     */
    protected function getMockSwiftMailer($methods = ['send'])
    {
        return $this->getMockBuilder(Swift_Mailer::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @param array $methods
     *
     * @return Storage|MockObject
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
     * @return Users|MockObject
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
     * @deprecated remove in v4 as PHPUnit 5 includes this
     *
     * @param mixed $originalClassName
     *
     * @return MockObject
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
