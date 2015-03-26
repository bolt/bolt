<?php

namespace Bolt\Database\Migration;

/**
 * Abstract base class for database import/export
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractMigration
{
    /** @var boolean */
    protected $error = false;

    /** @var array */
    protected $errorMessages = array();

    /**
     * Get the error state.
     *
     * @return boolean
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the error state.
     *
     * @param boolean $error
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get the error messages.
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * Add an error message to the error queue.
     *
     * @param string $errorMessage
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessages[] = $errorMessage;

        return $this;
    }
}
