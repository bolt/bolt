<?php

namespace Bolt\Tests\Logger;

use Bolt\Common\Json;
use Bolt\Logger\Handler\RecordChangeHandler;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\DoctrineMockBuilder;
use Monolog\Logger;
use PHPUnit\Framework\Assert;

/**
 * Class to test src/Logger/Handler/RecordChangeHandler.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RecordChangeHandlerTest extends BoltUnitTest
{
    public function testSetupInitialize()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);

        $log = new Logger('logger.system');
        $handler = new RecordChangeHandler($app);

        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $this->setService('db', $db);

        $log->pushHandler($handler);
        $log->addRecord(
            Logger::DEBUG,
            'test',
            [
                'action'      => 'UPDATE',
                'contenttype' => 'pages',
                'id'          => 1,
                'old'         => ['title' => 'test'],
                'new'         => ['title' => 'test2'],
                'comment'     => 'foo',
            ]
        );
        $this->assertEquals('bolt_log_change', Assert::readAttribute($handler, 'tablename'));
    }

    public function testHandle()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);

        $log = new Logger('logger.system');
        $handler = new RecordChangeHandler($app);

        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $db->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('bolt_log_change'),
                $this->callback(
                    function ($arg) {
                        return isset($arg['diff']) && $arg['diff'] === '{"title":["test","test2"]}';
                    }
                )
            );

        $this->setService('db', $db);

        $log->pushHandler($handler);
        $log->addRecord(
            Logger::DEBUG,
            'test',
            [
                'action'      => 'UPDATE',
                'contenttype' => 'pages',
                'id'          => 1,
                'old'         => ['title' => 'test'],
                'new'         => ['title' => 'test2'],
                'comment'     => 'An Update',
            ]
        );
    }

    public function testNotHandling()
    {
        $app = $this->getApp();
        $handler = new RecordChangeHandler($app, Logger::WARNING);
        $this->assertFalse($handler->handle(['level' => 100]));
    }

    public function provideDiff()
    {
        return [
//            'No features at all' => [
//                [],
//                [],
//                [],
//            ],
//            'Feature one stays the same' => [
//                ['feature one' => 'old feature', 'constant' => 'value'],
//                ['feature one' => 'old feature', 'constant' => 'value'],
//                [],
//            ],
//            'Feature one gets removed' => [
//                ['feature one' => 'old feature', 'constant' => 'value'],
//                ['constant' => 'value'],
//                [
//                    'feature one' => ['old feature', null],
//                ],
//            ],
            'Feature one gets added' => [
                ['title' => 'constant'],
                ['feature one' => 'new feature', 'title' => 'constant'],
                [
                    'feature one' => [null, 'new feature'],
                ],
            ],
            'Feature one gets updated' => [
                ['feature one' => 'old feature one', 'title' => 'constant'],
                ['feature one' => 'new feature one', 'title' => 'constant'],
                [
                    'feature one' => ['old feature one', 'new feature one'],
                ],
            ],
            'Multi feature one & two get updated' => [
                [
                    'feature one' => 'old feature one',
                    'feature two' => 'old feature two',
                    'title'       => 'constant',
                ],
                [
                    'feature one' => 'new feature one',
                    'feature two' => 'new feature two',
                    'title'       => 'constant',
                ],
                [
                    'feature one' => ['old feature one', 'new feature one'],
                    'feature two' => ['old feature two', 'new feature two'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideDiff
     *
     * @param array $a
     * @param array $b
     * @param array $expected
     */
    public function testDiff($a, $b, $expected)
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);

        $actual = null;
        $mocker = new DoctrineMockBuilder();
        $db = $mocker->getConnectionMock();
        $db->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('bolt_log_change'),
                $this->callback(
                    function ($arg) use (&$actual) {
                        $actual = Json::parse($arg['diff']);

                        return $arg;
                    }
                )
            )
        ;
        $this->setService('db', $db);

        $handler = new RecordChangeHandler($app);
        $handler->handle([
            'context' => [
                'action' => 'UPDATE',
                'old'    => $a,
                'new'    => $b,

                'id'          => 1,
                'comment'     => '',
                'contenttype' => 'asdf',
            ],
            'level'    => Logger::DEBUG,
            'datetime' => new \DateTime(),
            'extra'    => [],
        ]);

        $this->assertEquals($expected, $actual);
    }
}
