<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Storage\Entity\Authtoken;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository/Content
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class AuthtokenRepositoryTest extends BoltUnitTest
{
    public function testTokenQuery()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('Bolt\Storage\Entity\Authtoken');
        $query = $repo->queryExistingToken('user', 'ip', 'agent');
        $this->assertEquals('SELECT * FROM bolt_authtoken WHERE (username=:username) AND (ip=:ip) AND (useragent=:useragent)', $query->getSql());
    }

    
}
