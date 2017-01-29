<?php

namespace Bolt\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Template based response.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TemplateResponse extends Response
{
    /** @var string */
    protected $templateName;
    /** @var array */
    protected $context = [];
    /** @var array */
    protected $globals = [];

    /**
     * Constructor.
     *
     * @param string $templateName
     * @param array  $context
     * @param array  $globals
     */
    public function __construct($templateName, array $context = [], array $globals = [])
    {
        parent::__construct();
        $this->templateName = $templateName;
        $this->context = $context;
        $this->globals = $globals;
    }

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return $this->globals;
    }
}
