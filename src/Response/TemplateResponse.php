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
     * @param string   $template The template name
     * @param iterable $context  The context given to the template
     * @param mixed    $content  The response content, see setContent()
     * @param int      $status   The response status code
     * @param array    $headers  An array of response headers
     */
    public function __construct($template, $context = [], $content = '', $status = 200, $headers = [])
    {
        parent::__construct($content, $status, $headers);
        $this->template = $template;
        $this->setContext($context);
    }

    /**
     * Factory method for chainability.
     *
     * @param string   $template The template name
     * @param iterable $context  The context given to the template
     * @param mixed    $content  The response content, see setContent()
     * @param int      $status   The response status code
     * @param array    $headers  An array of response headers
     *
     * @return TemplateResponse
     */
    public static function create($template = '', $context = [], $content = '', $status = 200, $headers = [])
    {
        return new static($template, $context, $content, $status, $headers);
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
