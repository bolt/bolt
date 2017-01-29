<?php

namespace Bolt\Twig;

use Twig_Node as Node;

/**
 * Represents a switch node.
 *
 * @author Dsls
 * @author maxgalbu
 *
 * @see https://gist.github.com/maxgalbu/9409182
 */
class SwitchNode extends Node
{
    /**
     * Constructor.
     *
     * @param Node      $value
     * @param Node      $cases
     * @param Node|null $default
     * @param int       $lineNo
     * @param null      $tag
     */
    public function __construct(Node $value, Node $cases, Node $default = null, $lineNo = 0, $tag = null)
    {
        parent::__construct(['value' => $value, 'cases' => $cases, 'default' => $default], [], $lineNo, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        $compiler
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent()
        ;
        $cases = $this->getNode('cases');
        $count = count($cases);
        for ($i = 0; $i < $count; $i += 2) {
            $compiler
                ->write('case ')
                ->subcompile($cases->getNode($i))
                ->raw(":\n")
                ->indent()
                ->subcompile($cases->getNode($i + 1))
                ->write('')
                ->raw("break;\n")
            ;
        }

        if ($this->hasNode('default') && null !== $this->getNode('default')) {
            $compiler
                ->write("default:\n")
                ->indent()
                ->subcompile($this->getNode('default'))
                ->write('')
                ->raw("break;\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}
