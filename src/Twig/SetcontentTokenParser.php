<?php

namespace Bolt\Twig;

use Twig_Node_Expression_Array as NodeExpressionArray;
use Twig_Node_Expression_Constant as NodeExpressionConstant;
use Twig_Token as Token;
use Twig_TokenParser as TokenParser;

/**
 * Twig {{ setcontent }} token parser.
 *
 * @author Bob den Otter <bob@twokings.nl>
 */
class SetcontentTokenParser extends TokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $arguments = new NodeExpressionArray([], $lineno);
        $whereArguments = [];

        // name - the new variable with the results
        $name = $this->parser->getStream()->expect(Token::NAME_TYPE)->getValue();
        $this->parser->getStream()->expect(Token::OPERATOR_TYPE, '=');

        // ContentType, or simple expression to content.
        $contentType = $this->parser->getExpressionParser()->parseExpression();

        $counter = 0;

        do {
            // where parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'where')) {
                $this->parser->getStream()->next();
                $whereArguments = ['wherearguments' => $this->parser->getExpressionParser()->parseExpression()];
            }

            // limit parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'limit')) {
                $this->parser->getStream()->next();
                $limit = $this->parser->getExpressionParser()->parseExpression();
                $arguments->addElement($limit, new NodeExpressionConstant('limit', $lineno));
            }

            // order / orderby parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'order') ||
                $this->parser->getStream()->test(Token::NAME_TYPE, 'orderby')) {
                $this->parser->getStream()->next();
                $order = $this->parser->getExpressionParser()->parseExpression();
                $arguments->addElement($order, new NodeExpressionConstant('order', $lineno));
            }

            // paging / allowpaging parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'paging') ||
                $this->parser->getStream()->test(Token::NAME_TYPE, 'allowpaging')) {
                $this->parser->getStream()->next();
                $arguments->addElement(
                    new NodeExpressionConstant(true, $lineno),
                    new NodeExpressionConstant('paging', $lineno)
                );
            }

            // printquery parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'printquery')) {
                $this->parser->getStream()->next();
                $arguments->addElement(
                    new NodeExpressionConstant(true, $lineno),
                    new NodeExpressionConstant('printquery', $lineno)
                );
            }

            // returnsingle parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'returnsingle')) {
                $this->parser->getStream()->next();
                $arguments->addElement(
                    new NodeExpressionConstant(true, $lineno),
                    new NodeExpressionConstant('returnsingle', $lineno)
                );
            }

            // nohydrate parameter
            if ($this->parser->getStream()->test(Token::NAME_TYPE, 'nohydrate')) {
                $this->parser->getStream()->next();
                $arguments->addElement(
                    new NodeExpressionConstant(false, $lineno),
                    new NodeExpressionConstant('hydrate', $lineno)
                );
            }

            // Make sure we don't get stuck in a loop, if a token can't be parsed.
            $counter++;
        } while (!$this->parser->getStream()->test(Token::BLOCK_END_TYPE) && ($counter < 10));

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new SetcontentNode($name, $contentType, $arguments, $whereArguments, $lineno, $this->getTag());
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'setcontent';
    }
}
