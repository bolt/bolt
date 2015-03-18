<?php

namespace Bolt\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * BoltResponse represents a prepared Bolt HTTP response.
 *
 * A StreamedResponse uses a renderer and context variables 
 * to create the response content.
 *
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BoltResponse extends Response
{
    protected $renderer;
    protected $context = array();

    /**
     * Constructor.
     *
     * @param Renderer      $renderer An object that is able to render a template with context
     * @param array         $context  An array of context variables
     * @param int           $status   The response status code
     * @param array         $headers  An array of response headers
     *
     */
    public function __construct($renderer, $context = array(), $status = 200, $headers = array())
    {
        parent::__construct(null, $status, $headers);
        $this->renderer = $renderer;
        $this->context = $context;

    }

    /**
     * Factory method for chainability
     *
     * @param Renderer      $renderer An object that is able to render a template with context
     * @param array         $context  An array of context variables
     * @param int           $status   The response status code
     * @param array         $headers  An array of response headers
     *
     * @return BoltResponse
     */
    public static function create($renderer, $context = array(), $status = 200, $headers = array())
    {
        return new static($renderer, $context, $status, $headers);
    }

    /**
     * Sets the Renderer used to create this Response.
     *
     * @param Renderer $renderer A renderer object
     *
     */
    public function setRenderer($renderer)
    {
        $this->callback = $callback;
    }
    
    /**
     * Sets the context variables for this Response.
     *
     * @param array $context
     *
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
    
    /**
     * Returns the renderer.
     *
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
    
    /**
     * Returns the context.
     *
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     *
     * This method creates an output passing the context to the renderer.
     */
    public function sendContent()
    {
        $output = $this->getRenderer()->render($this->getContext());
        $this->setContent($output);
        echo $this->content;
 
        return $this;
    }

}
