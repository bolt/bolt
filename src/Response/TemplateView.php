<?php

namespace Bolt\Response;

use Bolt\Collection\MutableBag;
use Webmozart\Assert\Assert;

/**
 * A view that will be rendered with Twig and converted to a response.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class TemplateView
{
    /** @var string */
    protected $template;
    /** @var MutableBag */
    protected $context;

    /**
     * Constructor.
     *
     * @param string   $template
     * @param iterable $context
     */
    public function __construct($template, $context = [])
    {
        $this->setTemplate($template);
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
     * @param string $template
     *
     * @return TemplateView
     */
    public function setTemplate($template)
    {
        Assert::string($template);

        $this->template = $template;

        return $this;
    }

    /**
     * @return MutableBag
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param iterable $context
     *
     * @return TemplateView
     */
    public function setContext($context)
    {
        Assert::isTraversable($context);

        $this->context = MutableBag::from($context);

        return $this;
    }

    /**
     * Don't call directly.
     *
     * @internal
     */
    public function __clone()
    {
        $this->context = clone $this->context;
    }
}
