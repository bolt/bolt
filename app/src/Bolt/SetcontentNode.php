<?php

namespace Bolt;

class SetcontentNode extends \Twig_Node
{
    public function __construct($name, $contenttype, $arguments, $lineno, $tag = null)
    {
        parent::__construct(array(), array('name' => $name, 'contenttype' => $contenttype, 'arguments' => $arguments), $lineno, $tag);
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
            ->raw(", " . var_export($arguments, true) )
            ->raw(" );\n");

    }
}
