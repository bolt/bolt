<?php

namespace Bolt\Twig;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
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
class SwitchTokenParser extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token A Token instance
     *
     * @throws SyntaxError
     *
     * @return Node A Twig node instance
     */
    public function parse(Token $token)
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $default = null;
        $cases = [];
        $end = false;

        $name = $parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);
        $stream->expect(Token::TEXT_TYPE);
        $stream->expect(Token::BLOCK_START_TYPE);
        while (!$end) {
            /** @var Token $v */
            $v = $stream->next();
            switch ($v->getValue()) {
                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $default = $parser->subparse([$this, 'decideIfEnd']);
                    break;

                case 'case':
                    $expr = $parser->getExpressionParser()->parseExpression();
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $parser->subparse([$this, 'decideIfFork']);
                    $cases[] = $expr;
                    $cases[] = $body;
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    $message = sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $v->getLine());

                    throw new SyntaxError($message, $v->getLine());
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($name, new Node($cases), $default, $token->getLine(), $this->getTag());
    }

    /**
     * @param Token $token
     *
     * @return bool
     */
    public function decideIfFork(Token $token)
    {
        return $token->test(['case', 'default', 'endswitch']);
    }

    /**
     * @param Token $token
     *
     * @return bool
     */
    public function decideIfEnd(Token $token)
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
