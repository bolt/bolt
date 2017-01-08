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
     * Constructor.
     *
     * @param Template $template
     * @param array    $context
     * @param array    $globals
     */
    public function __construct(Template $template, array $context = [], array $globals = [])
    {
        parent::__construct();
        $this->template = $template;
        $this->context = $context;
        $this->globals = $globals;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
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
