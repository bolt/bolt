<?php

namespace Bolt\Twig;

use Bolt\Twig\Extension\BoltExtension;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig_Compiler as Compiler;

/**
 * Twig setcontent node.
 *
 * @author Bob den Otter <bob@twokings.nl>
 */
class SetcontentNode extends Node
{
    /**
     * Constructor.
     *
     * @param string          $name
     * @param Node            $contentType
     * @param ArrayExpression $arguments
     * @param array           $whereArguments
     * @param int             $lineNo
     * @param null            $tag
     */
    public function __construct($name, Node $contentType, ArrayExpression $arguments, array $whereArguments, $lineNo, $tag = null)
    {
        parent::__construct(
            $whereArguments,
            ['name' => $name, 'contenttype' => $contentType, 'arguments' => $arguments],
            $lineNo,
            $tag
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler)
    {
        $arguments = $this->getAttribute('arguments');

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
