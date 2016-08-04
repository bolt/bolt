<?php

namespace Bolt\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boot initialisation exception.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BootException extends RuntimeException
{
    /** @var Response */
    protected $response;

    /**
     * Constructor.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     * @param Response   $response
     */
    public function __construct($message, $code = 0, \Exception $previous = null, Response $response = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @return boolean
     */
    public function hasResponse()
    {
        return (boolean) $this->response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Output an exception *very* early in the load-chain.
     *
     * @param string $message
     *
     * @throws BootException
     */
    public static function earlyException($message)
    {
        echo $message;

        throw self::__construct(strip_tags($message));
    }

}
