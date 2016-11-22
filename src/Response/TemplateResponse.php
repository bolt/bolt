<?php

namespace Bolt\Response;

use Symfony\Component\HttpFoundation\Response;
use Twig_Template as Template;

/**
 * Template based response.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TemplateResponse extends Response
{
    /** @var Template */
    protected $template;
    /** @var array */
    protected $context = [];
    /** @var array */
    protected $globals = [];

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param Template $template
     *
     * @return TemplateResponse
     */
    public function setTemplate(Template $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     *
     * @return TemplateResponse
     */
    public function setContext(array $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return $this->globals;
    }

    /**
     * @param array $globals
     *
     * @return TemplateResponse
     */
    public function setGlobals(array $globals)
    {
        $this->globals = $globals;

        return $this;
    }
}
