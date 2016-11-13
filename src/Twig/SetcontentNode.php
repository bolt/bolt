<?php

namespace Bolt\Twig;

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
            ->write('$template_storage = $context[\'app\'][\'storage\'];' . "\n")
            ->write('$context[\'' . $this->getAttribute('name') . '\'] = ')
            ->write('$template_storage->getContent(')
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
