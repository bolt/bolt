<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository/UsersRepository
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

        $queryDelUserById = $repo->deleteUserQuery(1);
        $this->assertEquals('DELETE FROM bolt_users WHERE id = :userId', $queryDelUserById->getSql());

        $queryDelUserByName = $repo->deleteUserQuery('user');
        $this->assertEquals('DELETE FROM bolt_users WHERE (username = :userId) OR (email = :userId)', $queryDelUserByName->getSql());

        $queryGetUserByID = $repo->getUserQuery(1);
        $this->assertEquals('SELECT * FROM bolt_users users WHERE id = :userId', $queryGetUserByID->getSql());

        $queryGetUserByName = $repo->getUserQuery('user');
        $this->assertEquals('SELECT * FROM bolt_users users WHERE (username LIKE :userId) OR (email = :userId)', $queryGetUserByName->getSql());

        $queryHasUsers = $repo->hasUsersQuery();
        $this->assertEquals('SELECT COUNT(id) as count FROM bolt_users users', $queryHasUsers->getSql());

        $queryUserShadowAuth = $repo->getUserShadowAuthQuery('shadowtoken');
        $this->assertEquals('SELECT * FROM bolt_users users WHERE (shadowtoken = :shadowtoken) AND (shadowvalidity > :shadowvalidity)', $queryUserShadowAuth->getSql());
    }
}
