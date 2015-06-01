<?php
namespace Bolt\Configuration\Check;

/**
 * A container class for a check result.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Result implements \JsonSerializable
{
    /** @var boolean */
    protected $pass;
    /** @var string */
    protected $message;
    /** @var \Exception */
    protected $exception;

    /**
     * Check if the result is a pass or fail.
     *
     * @return boolean
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
     * @return boolean
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
     * Valid output for json_encode()
     *
     * @return array
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
