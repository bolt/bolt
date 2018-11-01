<?php

namespace Bolt\Twig;

use Bolt\Common\Deprecated;
use Bolt\Twig\Extension\BoltExtension;
use Bolt\Twig\Runtime\BoltRuntime;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

/**
 * Twig setcontent node.
 *
 * @author Bob den Otter <bob@twokings.nl>
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SetcontentNode extends Node
{
    /** @var bool */
    private $legacy;

    /**
     * Constructor.
     *
     * @param string          $name
     * @param Node            $contentType
     * @param ArrayExpression $arguments
     * @param array           $whereArguments
     * @param int             $lineNo
     * @param null            $tag
     * @param bool            $legacy
     */
    public function __construct($name, Node $contentType, ArrayExpression $arguments, array $whereArguments, $lineNo, $tag = null, $legacy = false)
    {
        parent::__construct(
            $whereArguments,
            ['name' => $name, 'contenttype' => $contentType, 'arguments' => $arguments],
            $lineNo,
            $tag
        );
        $this->legacy = $legacy;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler)
    {
        $arguments = $this->getAttribute('arguments');

        if ($this->legacy) {
            $this->compileLegacy($compiler, $arguments);

            return;
        }

        $compiler
            ->addDebugInfo($this)
            ->write("\$context['")
            ->raw($this->getAttribute('name'))
            ->raw("'] = ")
            ->raw("\$this->env->getRuntime('" . BoltRuntime::class . "')->getQueryEngine()->getContentForTwig(")
            ->subcompile($this->getAttribute('contenttype'))
            ->raw(', ')
            ->subcompile($arguments)
        ;

        if ($this->hasNode('wherearguments')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('wherearguments'))
            ;
        }

        $compiler->raw(" );\n");
    }

    /**
     * @param Compiler $compiler
     * @param Node     $arguments
     *
     * @deprecated Deprecated since 3.4, to be removed in 4.0.
     */
    private function compileLegacy(Compiler $compiler, Node $arguments)
    {
        Deprecated::warn('Calling {{ setcontent }} in legacy storage mode', 3.4, "Set 'compatibility/setcontent_legacy: false' in config.yml");

        $compiler
            ->addDebugInfo($this)
            ->write("\$context['")
            ->raw($this->getAttribute('name'))
            ->raw("'] = ")
            ->raw("\$this->env->getExtension('" . BoltExtension::class . "')->getStorage()->getContent(")
            ->subcompile($this->getAttribute('contenttype'))
            ->raw(', ')
            ->subcompile($arguments)
        ;

        if ($this->hasNode('wherearguments')) {
            $compiler
                ->raw(', $pager, ')
                ->subcompile($this->getNode('wherearguments'))
            ;
        }

        $compiler->raw(" );\n");
    }
}
