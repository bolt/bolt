<?php

namespace Bolt\Twig;

use Bolt\Twig\Extension\BoltExtension;
use Twig_Node as Node;
use Twig_Node_Expression_Array as NodeExpressionArray;

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
     * @param string              $name
     * @param Node                $contentType
     * @param NodeExpressionArray $arguments
     * @param array               $whereArguments
     * @param int                 $lineNo
     * @param null                $tag
     */
    public function __construct($name, Node $contentType, NodeExpressionArray $arguments, array $whereArguments, $lineNo, $tag = null)
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
    public function compile(\Twig_Compiler $compiler)
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
