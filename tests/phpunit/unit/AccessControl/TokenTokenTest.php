<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Token\Token;
use Bolt\Storage\Entity;

/**
 * Test for AccessControl\Token\Token
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TokenTokenTest extends BoltUnitTest
{
    public function testConstructor()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf('Bolt\AccessControl\Token\Token', $token);
    }
}
