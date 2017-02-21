<?php

namespace Bolt\AccessControl;

use Bolt\Exception\PasswordHashException;
use Bolt\Exception\PasswordLegacyHashException;

/**
 * Password hash management service.
 *
 * @deprecated Deprecated since 4.0-dev to be removed in 4.0.0.
 *             This is a transitory service to allow for the replacement of
 *             PasswordLib, while migrating to Symfony Security.
 *             DO NOT RELY ON IT BEING AVAILABLE IN A STABLE RELEASE!
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PasswordHashManager
{
    /** @var int */
    protected $algorithm;
    /** @var array */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param int   $algorithm
     * @param array $options
     */
    public function __construct($algorithm = PASSWORD_DEFAULT, array $options = [])
    {
        $this->algorithm = $algorithm;
        $this->options = $options;
    }

    /**
     * Create a hash for a given password.
     *
     * @param $password
     *
     * @throws PasswordHashException
     *
     * @return bool|string
     */
    public function createHash($password)
    {
        if (strlen($password) < 6) {
            throw new PasswordHashException('Can not save a password with a length shorter than 6 characters!');
        }
        if ($this->algorithm === PASSWORD_BCRYPT && $this->isBlowfish($password)) {
            return $password;
        }
        if ($this->isPHPass($password)) {
            throw new PasswordLegacyHashException();
        }

        $hash = password_hash($password, $this->algorithm, $this->options);
        if ($hash === false) {
            throw new PasswordHashException('Unable to hash password.');
        }

        return $hash;
    }

    /**
     * Determine if a password matches the stored hash.
     *
     * @param string $password
     * @param string $hash
     *
     * @throws PasswordHashException
     *
     * @return bool
     */
    public function verifyHash($password, $hash)
    {
        if ($this->isPHPass($password)) {
            throw new PasswordLegacyHashException();
        }

        return password_verify($password, $hash);
    }

    /**
     * Determine if the hash was made with PHPass.
     *
     * @param string $hash
     *
     * @return bool
     */
    private function isPHPass($hash)
    {
        $prefix = preg_quote('$P$', '/');

        return (bool) preg_match('/^'.$prefix.'[a-zA-Z0-9.\/]{31}$/', $hash);
    }

    /**
     * Determine if the hash was made with Blowfish.
     *
     * @param string $hash
     *
     * @return bool
     */
    private function isBlowfish($hash)
    {
        $regex = '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/';

        return (bool) preg_match($regex, $hash);
    }
}
