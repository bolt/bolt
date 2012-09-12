<?php

class Pilex_Setcontent_TokenParser extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, '=');
        $value = $this->parser->getExpressionParser()->parseExpression();

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Pilex_Setcontent_Node($name, $value, $lineno, $this->getTag());
    }

    public function getTag()
    {
        return 'setcontent';
    }
}


class Pilex_Setcontent_Node extends Twig_Node
{
    public function __construct($name, Twig_Node_Expression $value, $lineno, $tag = null)
    {
        parent::__construct(array('value' => $value), array('name' => $name), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$context[\''.$this->getAttribute('name').'\'] = ')
            ->subcompile($this->getNode('value'))
            ->raw(";\n")
        ;
                
    }
}