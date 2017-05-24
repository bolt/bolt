<?php

namespace Bolt\Tests\Logger;

use Bolt\Logger\Manager;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository\LogChangeRepository;
use Bolt\Storage\Repository\LogSystemRepository;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\DoctrineMockBuilder;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Logger/Manager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LogManagerTest extends BoltUnitTest
{
    public function setUp()
    {
    }

    public function testSetup()
    {
        $app = $this->getApp();
        $log = $this->getLogManager($app);
        $this->assertObjectHasAttribute('changeRepository', $log);
        $this->assertObjectHasAttribute('systemRepository', $log);
    }

    public function testTrim()
    {
        $app = $this->getApp();
        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $db->expects($this->any())
            ->method('executeUpdate')
            ->with($this->equalTo('DELETE FROM bolt_log_system WHERE date < :date'));

        $this->setService('db', $db);
        $log = $this->getLogManager($app);
        $log->trim('system');
    }

    public function testChange()
    {
        $app = $this->getApp();
        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $db->expects($this->any())
            ->method('executeUpdate')
            ->with($this->equalTo('DELETE FROM bolt_log_change WHERE date < :date'));

        $this->setService('db', $db);
        $log = $this->getLogManager($app);
        $log->trim('change');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid log type requested: invalid
     */
    public function testInvalid()
    {
        $app = $this->getApp();
        $log = $this->getLogManager($app);
        $log->trim('invalid');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid log type requested: invalid
     */
    public function testClear()
    {
        $app = $this->getApp();
        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $db->expects($this->at(1))
            ->method('executeQuery')
            ->with($this->equalTo('TRUNCATE bolt_log_system'));

        $db->expects($this->at(2))
            ->method('executeQuery')
            ->with($this->equalTo('TRUNCATE bolt_log_change'));

        $this->setService('db', $db);
        $log = $this->getLogManager($app);
        $log->clear('system');
        $log->clear('change');

        $log->clear('invalid');
    }

    public function testGetActivity()
    {
        $app = $this->getApp();

        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $queries = [];
        $db->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnCallback(
                function ($query, $params) use (&$queries, $mocker) {
                    $queries[] = $query;

                    return $mocker->getStatementMock();
                }
            ));

        $this->setService('db', $db);
        $app['request'] = Request::createFromGlobals();

        $log = $this->getLogManager($app);
        $log->getActivity('system', 10);
    }

    public function testGetActivityChange()
    {
        $app = $this->getApp();

        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $queries = [];
        $db->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnCallback(
                function ($query, $params) use (&$queries, $mocker) {
                    $queries[] = $query;

                    return $mocker->getStatementMock();
                }
            ));

        $this->setService('db', $db);
        $app['request'] = Request::createFromGlobals();

        $log = $this->getLogManager($app);
        $log->getActivity('change', 10);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid log type requested: invalid
     */
    public function testGetActivityInvalid()
    {
        $app = $this->getApp();
        $log = $this->getLogManager($app);
        $log->getActivity('invalid', 10);
    }

    public function testGetActivityLevel()
    {
        $app = $this->getApp();

        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $queries = [];
        $db->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnCallback(
                function ($query, $params) use (&$queries, $mocker) {
                    $queries[] = $query;

                    return $mocker->getStatementMock();
                }
            ));

        $this->setService('db', $db);
        $app['request'] = Request::createFromGlobals();

        $log = $this->getLogManager($app);
        $log->getActivity('change', 10, 3, ['contenttype' => 'pages']);
    }

    /**
     * @param Application $app
     *
     * @return \Bolt\Logger\Manager
     */
    protected function getLogManager($app)
    {
        /** @var LogChangeRepository $changeRepository */
        $changeRepository = $app['storage']->getRepository(Entity\LogChange::class);
        /** @var LogSystemRepository $systemRepository */
        $systemRepository = $app['storage']->getRepository(Entity\LogSystem::class);

        return new Manager($app, $changeRepository, $systemRepository);
    }
}
