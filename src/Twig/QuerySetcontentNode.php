<?php

namespace Bolt\Twig;

use Bolt\Twig\Runtime\BoltRuntime;
use Twig_Compiler as Compiler;

/**
 * New Twig setcontent node which points to the new Query engine
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QuerySetcontentNode extends SetcontentNode
{

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
            ->raw("\$this->env->getRuntime('" . BoltRuntime::class . "')->getQueryEngine()->getContent(")
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
