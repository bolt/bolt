<?php

namespace Bolt\Configuration\Check;

use Bolt\Common\Deprecated;

/**
 * A container class for a check result.
 *
 * @deprecated Since 3.4, to be removed in 4.0
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Result implements \JsonSerializable
{
    /** @var bool */
    protected $pass;
    /** @var string */
    protected $message;
    /** @var \Exception */
    protected $exception;

    public function __construct()
    {
        Deprecated::cls(__CLASS__, 3.4);
    }

    /**
     * Check if the result is a pass or fail.
     *
     * @return bool
     */
    public function isPass()
    {
        return $this->pass;
    }

    /**
     * Set a pass condition for the check.
     *
     * @return \Bolt\Configuration\Check\Result
     */
    public function pass()
    {
        $this->pass = true;

        return $this;
    }

    /**
     * Set a fail condition for the check.
     *
     * @return \Bolt\Configuration\Check\Result
     */
    public function fail()
    {
        $this->pass = false;

        return $this;
    }

    /**
     * Check if the result contains an exception.
     *
     * @return bool
     */
    public function isException()
    {
        if ($this->exception === null) {
            return false;
        }

        return true;
    }

    /**
     * Set the message that describes the check result.
     *
     * @param string $message
     *
     * @return \Bolt\Configuration\Check\Result
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the message that describes the check result.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the exception that occurred during the check.
     *
     * @param \Exception $exception
     *
     * @return \Bolt\Configuration\Check\Result
     */
    public function setException(\Exception $exception)
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * Get the exception that occurred during the check.
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $arr = [];
        foreach (array_keys(get_class_vars(__CLASS__)) as $property) {
            if ($this->$property instanceof \Exception) {
                $arr[$property] = (string) $this->$property;
            } else {
                $arr[$property] = $this->$property;
            }
        }

        return $arr;
    }
}
