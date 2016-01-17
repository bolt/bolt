<?php

namespace Bolt\Twig;

/**
 * Represents a switch node.
 *
 * @package    twig
 *
 * @author     Dsls
 * @author     maxgalbu
 *
 * @see        https://gist.github.com/maxgalbu/9409182
 */
class SwitchNode extends \Twig_Node
{
    public function __construct(\Twig_NodeInterface $value, \Twig_NodeInterface $cases, \Twig_NodeInterface $default = null, $lineno = 0, $tag = null)
    {
        parent::__construct(['value' => $value, 'cases' => $cases, 'default' => $default], [], $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        $compiler
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent
        ;
        for ($i = 0; $i < count($this->getNode('cases')); $i += 2) {
            $compiler
                ->write('case ')
                ->subcompile($this->getNode('cases')->getNode($i))
                ->raw(":\n")
                ->indent()
                ->subcompile($this->getNode('cases')->getNode($i + 1))
                ->addIndentation()
                ->raw("break;\n")
            ;
        }

        if ($this->hasNode('default') && null !== $this->getNode('default')) {
            $compiler
                ->write("default:\n")
                ->indent()
                ->subcompile($this->getNode('default'))
                ->addIndentation()
                ->raw("break;\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}
