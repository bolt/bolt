<?php

namespace Bolt\Tests\AccessControl;

use Bolt\AccessControl\PasswordHashManager;
use PHPUnit\Framework\TestCase;

/**
 * PasswordHash test
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PasswordHashTest extends TestCase
{
    public function testCreateHash()
    {
        $passwordHasher = new PasswordHashManager();
        $hash = $passwordHasher->createHash('dropbear');

        $this->assertSame($hash, $passwordHasher->createHash($hash));
    }

    /**
     * It should throw an exception if the password is shorter than 6 characters.
     *
     * @expectedException \Bolt\Exception\PasswordHashException
     * @expectedExceptionMessage Can not save a password with a length shorter than 6 characters!
     */
    public function testCreateHashShortPassword()
    {
        $passwordHasher = new PasswordHashManager();
        $passwordHasher->createHash('koala');
    }

    public function testCreateHashAlreadyBlowfish()
    {
        $passwordHasher = new PasswordHashManager();
        $hash = password_hash('dropbear', PASSWORD_BCRYPT);

        $this->assertSame($hash, $passwordHasher->createHash($hash));
    }

    /**
     * @expectedException \Bolt\Exception\PasswordLegacyHashException
     * @expectedExceptionMessageRegExp /This user password has is stored using the legacy PHPass algorithm, and is no longer secure to use/
     */
    public function testCreateHashAlreadyPHPass()
    {
        $passwordHasher = new PasswordHashManager();
        $passwordHasher->createHash('$P$6vinaigregCze.fI75./Bj..B51oL3.');
    }

    public function verifyHash()
    {
        $passwordHasher = new PasswordHashManager();
        $hash = password_hash('dropbear', PASSWORD_BCRYPT);

        $this->assertTrue($passwordHasher->verifyHash('dropbear', $hash));
    }

    /**
     * @expectedException \Bolt\Exception\PasswordLegacyHashException
     * @expectedExceptionMessageRegExp /This user password has is stored using the legacy PHPass algorithm, and is no longer secure to use/
     */
    public function verifyHashAlreadyPHPass()
    {
        $passwordHasher = new PasswordHashManager();
        $passwordHasher->verifyHash('dropbear', '$P$6vinaigregCze.fI75./Bj..B51oL3.');
    }
}
