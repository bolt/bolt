<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository/AuthtokenRepository
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class AuthtokenRepositoryTest extends BoltUnitTest
{
    public function testRepositoryQueries()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('Bolt\Storage\Entity\Authtoken');

        $query1 = $repo->getUserTokenQuery('user', 'ip', 'agent');
        $this->assertEquals('SELECT * FROM bolt_authtoken WHERE (username = :username) AND (ip = :ip) AND (useragent = :useragent)', $query1->getSql());

        $query2 = $repo->getTokenQuery('token', 'ip', 'agent');
        $this->assertEquals('SELECT * FROM bolt_authtoken WHERE (token = :token) AND (ip = :ip) AND (useragent = :useragent)', $query2->getSql());

        $query3 = $repo->deleteTokensQuery('username');
        $this->assertEquals('DELETE FROM bolt_authtoken WHERE username = :username', $query3->getSql());

        $query4 = $repo->deleteExpiredTokensQuery();
        $this->assertEquals('DELETE FROM bolt_authtoken WHERE validity < :now', $query4->getSql());

        $query5 = $repo->getActiveSessionsQuery();
        $this->assertEquals('SELECT * FROM bolt_authtoken', $query5->getSql());
    }
}
