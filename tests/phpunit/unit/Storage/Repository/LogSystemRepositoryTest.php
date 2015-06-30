<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Tests\BoltUnitTest;
use Psr\Log\LogLevel;

/**
 * Class to test src/Storage/Repository/LogSystemRepository
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogSystemRepositoryTest extends BoltUnitTest
{
    public function testRepositoryQueries()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('Bolt\Storage\Entity\LogSystem');

        $queryTrimLog = $repo->queryTrimLog(7);
        $this->assertEquals(
            'DELETE FROM bolt_log_system WHERE date < :date',
            $queryTrimLog->getSql());

        $query = $repo->getActivityQuery(1, 10, ['level' => LogLevel::CRITICAL, 'context' => 'system']);
        $this->assertEquals(
            'SELECT * FROM bolt_log_system WHERE (level = :level) AND (context = :context) ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('critical', $params['level']);
        $this->assertEquals('system', $params['context']);

        $query = $repo->getActivityQuery(1, 10, ['level' => LogLevel::NOTICE, 'context' => ['system', 'twig']]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_system WHERE (level = :level) AND ((context = :context_0) OR (context = :context_1)) ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('notice', $params['level']);
        $this->assertEquals('system', $params['context_0']);
        $this->assertEquals('twig', $params['context_1']);

        $query = $repo->getActivityCountQuery(['level' => LogLevel::WARNING, 'context' => 'system']);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_system WHERE (level = :level) AND (context = :context)',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('warning', $params['level']);
        $this->assertEquals('system', $params['context']);

        $query = $repo->getActivityCountQuery(['level' => LogLevel::DEBUG, 'context' => ['system', 'twig']]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_system WHERE (level = :level) AND ((context = :context_0) OR (context = :context_1))',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('debug', $params['level']);
        $this->assertEquals('system', $params['context_0']);
        $this->assertEquals('twig', $params['context_1']);
    }
}
