<?php

namespace Bolt\Twig;

use Twig_Error_Syntax;
use Twig_Node;
use Twig_Token;
use Twig_TokenParser;

/*
 * Adapted from code originally in Twig/extensions.
 *
 * Usage:
 *
 * {% set foo = 1 %}
 * {% switch foo %}
 *     {% case 1 %}
 *         Foo was equal to the number one.
 *     {% case 2 %}
 *         Foo was two.
 *     {% default %}
 *         This is the default fallback.
 * {% endswitch %}
 *
 *
 * @see: https://gist.github.com/maxgalbu/9409182
 */
class SwitchTokenParser extends Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @throws Twig_Error_Syntax
     *
     * @return Twig_Node A Twig node instance
     */
    public function parse(Twig_Token $token)
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $default = null;
        $cases = [];
        $end = false;

        $name = $parser->getExpressionParser()->parseExpression();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $stream->expect(Twig_Token::TEXT_TYPE);
        $stream->expect(Twig_Token::BLOCK_START_TYPE);
        while (!$end) {
            /** @var Twig_Token $v */
            $v = $stream->next();
            switch ($v->getValue()) {
                case 'default':
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    $default = $parser->subparse([$this, 'decideIfEnd']);
                    break;

                case 'case':
                    $expr = $parser->getExpressionParser()->parseExpression();
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    $body = $parser->subparse([$this, 'decideIfFork']);
                    $cases[] = $expr;
                    $cases[] = $body;
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    $message = sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $v->getLine());

                    throw new Twig_Error_Syntax($message, $v->getLine());
            }
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new SwitchNode($name, new Twig_Node($cases), $default, $token->getLine(), $this->getTag());
    }

    /**
     * @param Twig_Token $token
     *
     * @return bool
     */
    public function decideIfFork(Twig_Token $token)
    {
        return $token->test(['case', 'default', 'endswitch']);
    }

    /**
     * @param Twig_Token $token
     *
     * @return bool
     */
    public function decideIfEnd(Twig_Token $token)
    {
        return $token->test(['endswitch']);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'switch';
    }
}
