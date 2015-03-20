<?php

namespace Bolt\Response;

use Symfony\Component\HttpFoundation\Response;
use \Twig_Template as Template;

/**
 * BoltResponse represents a prepared Bolt HTTP response.
 *
 * A StreamedResponse uses a renderer and context variables
 * to create the response content.
 *
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltResponse extends Response
{
    /** @var Template */
    protected $template;
    protected $context = array();
    protected $compiled = false;

    /**
     * Constructor.
     *
     * @param Template $template An object that is able to render a template with context
     * @param array    $context  An array of context variables
     * @param int      $status   The response status code
     * @param array    $headers  An array of response headers
     */
    public function __construct(Template $template, $context = array(), $status = 200, $headers = array())
    {
        parent::__construct(null, $status, $headers);
        $this->template = $template;
        $this->context = $context;
    }

    /**
     * Factory method for chainability
     *
     * @param Template $template An object that is able to render a template with context
     * @param array    $context  An array of context variables
     * @param int      $status   The response status code
     * @param array    $headers  An array of response headers
     *
     * @return BoltResponse
     */
    public static function create(Template $template = null, $context = array(), $status = 200, $headers = array())
    {
        return new static($template, $context, $status, $headers);
    }

    /**
     * Sets the Renderer used to create this Response.
     *
     * @param Template $template A template object
     */
    public function setTemplate(Template $template)
    {
        $this->template = $template;
    }
    
    /**
     * Sets the context variables for this Response.
     *
     * @param array $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
    
    /**
     * Returns the template.
     */
    public function getTemplate()
    {
        return $this->template;
    }
    
    /**
     * Returns the context.
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     * Gets globals from the template.
     */
    public function getGlobalContext()
    {
        return $this->template->getEnvironment()->getGlobals();
    }
    
    /**
     * Gets the name of the main loaded template.
     */
    public function getTemplateName()
    {
        return $this->template->getTemplateName();
    }
    
    /**
     * Returns the Response as a string.
     *
     * @return string The Response as an HTTP string
     */
    public function __toString()
    {
        return $this->getContent();
    }
    
    /**
     * Gets content for the current web response.
     *
     * @return Response
     */
    public function getContent()
    {
        if (!$this->compiled) {
            $this->compile();
        }

        return parent::getContent();
    }
    
    /**
     * Compiles the template using the context.
     *
     * @return void
     */
    public function compile()
    {
        $output = $this->template->render($this->context);
        $this->setContent($output);
        $this->compiled = true;
    }
}
