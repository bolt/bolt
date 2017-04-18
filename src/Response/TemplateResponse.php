<?php

namespace Bolt\Response;

use Bolt\Collection\ImmutableBag;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

/**
 * Template based response.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TemplateResponse extends Response
{
    /** @var string */
    protected $template;
    /** @var ImmutableBag */
    protected $context;

    /**
     * Constructor.
     *
     * @param string   $template
     * @param iterable $context
     */
    public function __construct($template, $context = [])
    {
        parent::__construct();
        $this->template = $template;
        $this->setContext($context);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return ImmutableBag
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param iterable $context
     */
    protected function setContext($context)
    {
        Assert::isTraversable($context);

        $this->context = ImmutableBag::from($context);
    }

    /**
     * Don't call directly.
     *
     * @internal
     */
    public function __clone()
    {
        parent::__clone();
        $this->context = clone $this->context;
    }
}
