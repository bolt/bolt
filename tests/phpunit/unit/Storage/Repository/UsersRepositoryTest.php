<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository/Content
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UsersRepositoryTest extends BoltUnitTest
{
    public function testRepositoryQueries()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('Bolt\Storage\Entity\Users');

        $query1 = $repo->deleteUserQuery('user');
        $this->assertEquals('DELETE FROM bolt_users WHERE (id = :userId) OR (username = :userId) OR (email = :userId)', $query1->getSql());

        $query2 = $repo->getUserQuery('user');
        $this->assertEquals('SELECT * FROM bolt_users WHERE (id = :userId) OR (username = :userId) OR (email = :userId)', $query2->getSql());

        $query3 = $repo->hasUsersQuery();
        $this->assertEquals('SELECT COUNT(id) as count FROM bolt_users', $query3->getSql());

        $query4 = $repo->getUserShadowAuthQuery('shadowtoken');
        $this->assertEquals('SELECT * FROM bolt_users WHERE (shadowtoken = :shadowtoken) AND (shadowvalidity > :shadowvalidity)', $query4->getSql());
    }
}
