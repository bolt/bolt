<?php

namespace Bolt\Twig;

class SetcontentNode extends \Twig_Node
{
    public function __construct($name, $contenttype, \Twig_Node_Expression_Array $arguments, $wherearguments, $lineno, $tag = null)
    {
        parent::__construct(
            array('wherearguments' => $wherearguments),
            array('name'           => $name, 'contenttype' => $contenttype, 'arguments' => $arguments),
            $lineno,
            $tag
        );
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $arguments = $this->getAttribute('arguments');

        $compiler
            ->addDebugInfo($this)
            ->write('$template_storage = new Bolt\Storage($context[\'app\']);' . "\n")
            ->write('$context[\'' . $this->getAttribute('name') . '\'] = ')
            ->write('$template_storage->getContent(')
            ->subcompile($this->getAttribute('contenttype'))
            ->raw(", ")
            ->subcompile($arguments);

        if (!is_null($this->getNode('wherearguments'))) {
            $compiler
                ->raw(', $pager, ')
                ->subcompile($this->getNode('wherearguments'));
        }

        $compiler->raw(" );\n");
    }
}
