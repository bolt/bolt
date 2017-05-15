<?php

namespace Bolt\Tests\Storage\Repository;

use Bolt\Storage\Entity;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Storage\Repository\AuthtokenRepository
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class AuthtokenRepositoryTest extends BoltUnitTest
{
    public function testUserTokenQuery()
    {
        $repo = $this->getRepository();
        $query = $repo->getUserTokenQuery(42, 'ip', 'agent');

        $this->assertEquals('SELECT * FROM bolt_authtoken authtoken WHERE (user_id = :user_id) AND (ip = :ip) AND (useragent = :useragent)', $query->getSql());
    }

    public function testTokenQuery()
    {
        $repo = $this->getRepository();
        $query = $repo->getTokenQuery('token', 'ip', 'agent');

        $this->assertEquals('SELECT * FROM bolt_authtoken authtoken WHERE (token = :token) AND (ip = :ip) AND (useragent = :useragent)', $query->getSql());
    }

    public function testDeleteTokensQuery()
    {
        $repo = $this->getRepository();
        $query = $repo->deleteTokensQuery(42);

        $this->assertEquals('DELETE FROM bolt_authtoken WHERE user_id = :user_id', $query->getSql());
    }

    public function testRepositoryQueries()
    {
        $repo = $this->getRepository();
        $query = $repo->deleteExpiredTokensQuery();

        $this->assertEquals('DELETE FROM bolt_authtoken WHERE validity < :now', $query->getSql());
    }

    public function testActiveSessionsQuery()
    {
        $repo = $this->getRepository();
        $query = $repo->getActiveSessionsQuery();

        $this->assertEquals('SELECT * FROM bolt_authtoken authtoken', $query->getSql());
    }

    /**
     * @return \Bolt\Storage\Repository\AuthtokenRepository
     */
    protected function getRepository()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];

        return $em->getRepository(Entity\Authtoken::class);
    }
}
