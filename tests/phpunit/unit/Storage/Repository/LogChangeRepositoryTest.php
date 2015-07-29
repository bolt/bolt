<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository/LogChangeRepository
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogChangeRepositoryTest extends BoltUnitTest
{
    public function testRepositoryQueries()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('Bolt\Storage\Entity\LogChange');

        $query = $repo->queryTrimLog(7);
        $this->assertEquals(
            'DELETE FROM bolt_log_change WHERE date < :date', $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals(7, $params['date']);

        $query = $repo->getActivityQuery(1, 10, []);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();

        $query = $repo->getActivityQuery(1, 10, ['contenttype' => 'pages']);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change WHERE contenttype = :contenttype ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype']);

        $query = $repo->getActivityQuery(1, 10, ['contenttype' => ['pages', 'entries']]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change WHERE (contenttype = :contenttype_0) OR (contenttype = :contenttype_1) ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype_0']);
        $this->assertEquals('entries', $params['contenttype_1']);

        $query = $repo->getActivityQuery(1, 10, ['contenttype' => 'pages', 'contentid' => 123]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change WHERE (contenttype = :contenttype) AND (contentid = :contentid) ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype']);
        $this->assertEquals(123, $params['contentid']);

        $query = $repo->getActivityQuery(1, 10, ['contenttype' => ['pages', 'entries'], 'contentid' => [2, 4]]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change WHERE ((contenttype = :contenttype_0) OR (contenttype = :contenttype_1)) AND ((contentid = :contentid_0) OR (contentid = :contentid_1)) ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype_0']);
        $this->assertEquals('entries', $params['contenttype_1']);
        $this->assertEquals(2, $params['contentid_0']);
        $this->assertEquals(4, $params['contentid_1']);

        $query = $repo->getActivityQuery(1, 10, ['ownerid' => 1]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change WHERE ownerid = :ownerid ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals(1, $params['ownerid']);

        $query = $repo->getActivityQuery(1, 10, ['contenttype' => 'pages', 'contentid' => 1, 'ownerid' => 42]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change WHERE (contenttype = :contenttype) AND (contentid = :contentid) AND (ownerid = :ownerid) ORDER BY id DESC LIMIT 10 OFFSET 0',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype']);
        $this->assertEquals(1, $params['contentid']);
        $this->assertEquals(42, $params['ownerid']);

        $query = $repo->getActivityCountQuery([]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change',
            $query->getSql());

        $query = $repo->getActivityCountQuery(['contenttype' => 'pages']);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE contenttype = :contenttype',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype']);

        $query = $repo->getActivityCountQuery(['contenttype' => ['pages', 'entries']]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE (contenttype = :contenttype_0) OR (contenttype = :contenttype_1)',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype_0']);
        $this->assertEquals('entries', $params['contenttype_1']);

        $query = $repo->getActivityCountQuery(['contenttype' => 'pages', 'contentid' => 1]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE (contenttype = :contenttype) AND (contentid = :contentid)',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype']);
        $this->assertEquals(1, $params['contentid']);

        $query = $repo->getActivityCountQuery(['contenttype' => ['pages', 'entries'], 'contentid' => [2, 4]]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE ((contenttype = :contenttype_0) OR (contenttype = :contenttype_1)) AND ((contentid = :contentid_0) OR (contentid = :contentid_1))',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype_0']);
        $this->assertEquals('entries', $params['contenttype_1']);
        $this->assertEquals(2, $params['contentid_0']);
        $this->assertEquals(4, $params['contentid_1']);

        $query = $repo->getActivityCountQuery(['ownerid' => 1]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE ownerid = :ownerid',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals(1, $params['ownerid']);

        $query = $repo->getActivityCountQuery(['contenttype' => 'pages', 'contentid' => 1, 'ownerid' => 42]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE (contenttype = :contenttype) AND (contentid = :contentid) AND (ownerid = :ownerid)',
            $query->getSql());
        $params = $query->getParameters();
        $this->assertEquals('pages', $params['contenttype']);
        $this->assertEquals(1, $params['contentid']);
        $this->assertEquals(42, $params['ownerid']);

        $query = $repo->getChangeLogQuery([]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change',
            $query->getSql());

        $query = $repo->getChangeLogQuery(['limit' => 42]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change LIMIT 42',
            $query->getSql());

        $query = $repo->getChangeLogQuery(['limit' => 42, 'offset' => 21]);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change LIMIT 42 OFFSET 21',
            $query->getSql());

        $query = $repo->getChangeLogQuery(['order' => 'chips']);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change ORDER BY chips ASC',
            $query->getSql());

        $query = $repo->getChangeLogQuery(['order' => 'chips', 'direction' => 'DESC']);
        $this->assertEquals(
            'SELECT * FROM bolt_log_change ORDER BY chips DESC',
            $query->getSql());

        $query = $repo->countChangeLogQuery();
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change',
            $query->getSql());

        $query = $repo->getChangeLogByContentTypeQuery('pages', []);
        $this->assertEquals(
            'SELECT bolt_log_change.*, bolt_log_change.title FROM bolt_log_change LEFT JOIN bolt_pages content ON content.id = bolt_log_change.contentid WHERE contenttype = :contenttype',
            $query->getSql());

        $query = $repo->getChangeLogByContentTypeQuery('pages', ['limit' => 1555]);
        $this->assertEquals(
            'SELECT bolt_log_change.*, bolt_log_change.title FROM bolt_log_change LEFT JOIN bolt_pages content ON content.id = bolt_log_change.contentid WHERE contenttype = :contenttype LIMIT 1555',
            $query->getSql());

        $query = $repo->getChangeLogByContentTypeQuery('pages', ['order' => 'foo']);
        $this->assertEquals(
            'SELECT bolt_log_change.*, bolt_log_change.title FROM bolt_log_change LEFT JOIN bolt_pages content ON content.id = bolt_log_change.contentid WHERE contenttype = :contenttype ORDER BY foo ASC',
            $query->getSql());

        $query = $repo->getChangeLogByContentTypeQuery('pages', ['order' => 'foo', 'direction' => 'DESC']);
        $this->assertEquals(
            'SELECT bolt_log_change.*, bolt_log_change.title FROM bolt_log_change LEFT JOIN bolt_pages content ON content.id = bolt_log_change.contentid WHERE contenttype = :contenttype ORDER BY foo DESC',
            $query->getSql());

        $query = $repo->getChangeLogByContentTypeQuery('pages', ['contentid' => 2]);
        $this->assertEquals(
            'SELECT bolt_log_change.*, bolt_log_change.title FROM bolt_log_change LEFT JOIN bolt_pages content ON content.id = bolt_log_change.contentid WHERE (contenttype = :contenttype) AND (contentid = :contentid)',
            $query->getSql());

        $query = $repo->getChangeLogByContentTypeQuery('pages', ['contentid' => 2, 'id', 4]);
        $this->assertEquals(
            'SELECT bolt_log_change.*, bolt_log_change.title FROM bolt_log_change LEFT JOIN bolt_pages content ON content.id = bolt_log_change.contentid WHERE (contenttype = :contenttype) AND (contentid = :contentid)',
            $query->getSql());

        $query = $repo->countChangeLogByContentTypeQuery('pages', []);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE contenttype = :contenttype',
            $query->getSql());

        $query = $repo->countChangeLogByContentTypeQuery('pages', ['order' => 'foo']);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE contenttype = :contenttype',
            $query->getSql());

        $query = $repo->countChangeLogByContentTypeQuery('pages', ['contentid' => 2]);
        $this->assertEquals(
            'SELECT COUNT(id) as count FROM bolt_log_change WHERE (contenttype = :contenttype) AND (contentid = :contentid)',
            $query->getSql());

        $query = $repo->getChangeLogEntryQuery('showcases', 1, 2, '=');
        $this->assertEquals(
            'SELECT bolt_log_change.* FROM bolt_log_change LEFT JOIN bolt_showcases content ON content.id = bolt_log_change.contentid WHERE (bolt_log_change.id = :logid) AND (bolt_log_change.contentid = :contentid) AND (contenttype = :contenttype)',
            $query->getSql());

        $query = $repo->getChangeLogEntryQuery('showcases', 1, 2, '<');
        $this->assertEquals(
            'SELECT bolt_log_change.* FROM bolt_log_change LEFT JOIN bolt_showcases content ON content.id = bolt_log_change.contentid WHERE (bolt_log_change.id < :logid) AND (bolt_log_change.contentid = :contentid) AND (contenttype = :contenttype) ORDER BY date DESC',
            $query->getSql());

        $query = $repo->getChangeLogEntryQuery('showcases', 1, 2, '>');
        $this->assertEquals(
            'SELECT bolt_log_change.* FROM bolt_log_change LEFT JOIN bolt_showcases content ON content.id = bolt_log_change.contentid WHERE (bolt_log_change.id > :logid) AND (bolt_log_change.contentid = :contentid) AND (contenttype = :contenttype) ORDER BY date ASC',
            $query->getSql());
    }
}
