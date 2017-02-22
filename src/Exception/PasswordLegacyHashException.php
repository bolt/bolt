<?php

namespace Bolt\Exception;

/**
 * Exception thrown when attempting to use PHPass hashes.
 */
class PasswordLegacyHashException extends PasswordHashException
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->message = 'This user password has is stored using the legacy PHPass algorithm, and is no longer secure to use. ' .
            'Please use the password reset functionality to set a new password.';
    }
}
