<?php

namespace Bolt\Tests\Storage\Repository;

use Bolt\Storage\Entity;
use Bolt\Tests\BoltUnitTest;
use Psr\Log\LogLevel;

/**
 * @covers \Bolt\Storage\Repository\LogSystemRepository
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogSystemRepositoryTest extends BoltUnitTest
{
    public function testActivityQueryTrim()
    {
        $repo = $this->getRepository();

        $queryTrimLog = $repo->queryTrimLog(7);
        $this->assertEquals(
            'DELETE FROM bolt_log_system WHERE date < :date',
            $queryTrimLog->getSql()
        );
    }

    public function providerActivityQueryCritical()
    {
        if (PHP_VERSION_ID < 70100) {
            return [
                ['SELECT * FROM bolt_log_system log_system WHERE (level = :level) AND (context = :context) ORDER BY id DESC LIMIT 10 OFFSET 0'],
            ];
        }

        return [
            ['SELECT * FROM bolt_log_system log_system WHERE (level = :level) AND (context = :context) ORDER BY id DESC LIMIT 10'],
        ];
    }

    /**
     * @dataProvider providerActivityQueryCritical
     *
     * @param string $expected
     */
    public function testActivityQueryCritical($expected)
    {
        $repo = $this->getRepository();

        $query = $repo->getActivityQuery(1, 10, ['level' => LogLevel::CRITICAL, 'context' => 'system']);
        $this->assertEquals($expected, $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('critical', $params['level']);
        $this->assertEquals('system', $params['context']);
    }

    public function providerActivityQueryNotice()
    {
        if (PHP_VERSION_ID < 70100) {
            return [
                ['SELECT * FROM bolt_log_system log_system WHERE (level = :level) AND ((context = :context_0) OR (context = :context_1)) ORDER BY id DESC LIMIT 10 OFFSET 0'],
            ];
        }

        return [
            ['SELECT * FROM bolt_log_system log_system WHERE (level = :level) AND ((context = :context_0) OR (context = :context_1)) ORDER BY id DESC LIMIT 10'],
        ];
    }

    /**
     * @dataProvider providerActivityQueryNotice
     *
     * @param string $expected
     */
    public function testActivityQueryNotice($expected)
    {
        $repo = $this->getRepository();

        $query = $repo->getActivityQuery(1, 10, ['level' => LogLevel::NOTICE, 'context' => ['system', 'twig']]);
        $this->assertEquals($expected, $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('notice', $params['level']);
        $this->assertEquals('system', $params['context_0']);
        $this->assertEquals('twig', $params['context_1']);
    }

    public function testActivityCountQuery()
    {
        $repo = $this->getRepository();
        $query = $repo->getActivityCountQuery(['level' => LogLevel::WARNING, 'context' => 'system']);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_system log_system WHERE (level = :level) AND (context = :context)',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('warning', $params['level']);
        $this->assertEquals('system', $params['context']);

        $query = $repo->getActivityCountQuery(['level' => LogLevel::DEBUG, 'context' => ['system', 'twig']]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_system log_system WHERE (level = :level) AND ((context = :context_0) OR (context = :context_1))',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('debug', $params['level']);
        $this->assertEquals('system', $params['context_0']);
        $this->assertEquals('twig', $params['context_1']);
    }

    /**
     * @return \Bolt\Storage\Repository\LogSystemRepository
     */
    protected function getRepository()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];

        return $em->getRepository(Entity\LogSystem::class);
    }
}
