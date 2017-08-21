<?php

namespace Bolt\Tests\Twig;

use Bolt\Twig\SwitchNode;
use Bolt\Twig\SwitchTokenParser;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * Class to test Twig {{ switch }} token classes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SwitchTokenParserTest extends AbstractTestTokenParser
{
    public function testClass()
    {
        $switchTokenParser = new SwitchTokenParser();
        $this->assertInstanceOf(AbstractTokenParser::class, $switchTokenParser);
    }

    public function testGetTag()
    {
        $switchTokenParser = new SwitchTokenParser();
        $this->assertSame('switch', $switchTokenParser->getTag());
    }

    /**
     * {% set koala = 2 %}
     * {% switch koala %}
     *     {% case 1 %}
     *         One koala
     *     {% case 2 %}
     *         Two koalas
     *     {% default %}
     *         Drop bear
     * {% endswitch %}
     */
    public function testParse()
    {
        $name = 'koala';
        $caseChoice = 2;
        $caseTextOne = 'One koala';
        $caseTextTwo = 'Two koalas';
        $caseTextDefault = 'Drop bear';

        $streamTokens = [
            // {% set %} statement
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'set', 1),
            new Token(Token::NAME_TYPE, $name, 1),
            new Token(Token::OPERATOR_TYPE, '=', 1),
            new Token(Token::NUMBER_TYPE, $caseChoice, 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),

            // {% switch %} statement
            new Token(Token::BLOCK_START_TYPE, '', 2),
            new Token(Token::NAME_TYPE, 'switch', 2),
            new Token(Token::NAME_TYPE, $name, 2),
            new Token(Token::BLOCK_END_TYPE, '', 2),

            // Case 1
            new Token(Token::TEXT_TYPE, '', 3),
            new Token(Token::BLOCK_START_TYPE, '', 3),
            new Token(Token::NAME_TYPE, 'case', 3),
            new Token(Token::NUMBER_TYPE, 1, 3),
            new Token(Token::BLOCK_END_TYPE, '', 3),

            new Token(Token::TEXT_TYPE, $caseTextOne, 4),

            // Case 2
            new Token(Token::BLOCK_START_TYPE, '', 5),
            new Token(Token::NAME_TYPE, 'case', 5),
            new Token(Token::NUMBER_TYPE, 2, 5),
            new Token(Token::BLOCK_END_TYPE, '', 5),

            new Token(Token::TEXT_TYPE, $caseTextTwo, 6),

            // Default
            new Token(Token::BLOCK_START_TYPE, '', 7),
            new Token(Token::NAME_TYPE, 'default', 7),
            new Token(Token::BLOCK_END_TYPE, '', 7),

            new Token(Token::TEXT_TYPE, $caseTextDefault, 8),

            new Token(Token::BLOCK_START_TYPE, '', 90),
            new Token(Token::NAME_TYPE, 'endswitch', 91),
            new Token(Token::BLOCK_END_TYPE, '', 93),

            new Token(Token::EOF_TYPE, '', 99),
        ];
        $twigTokenStream = new TokenStream($streamTokens, new Source(null, 'clippy'));

        $parser = $this->getParser($twigTokenStream, new SwitchTokenParser());
        /** @var ModuleNode $nodeModule */
        $nodeModule = $parser->parse($twigTokenStream);
        /** @var Node $bodyNodes */
        $bodyNodes = $nodeModule->getNode('body')->getIterator()->current();

        /** @var SetNode $setNode */
        $setNode = $bodyNodes->getNode(0);
        /** @var SwitchNode $switchNode */
        $switchNode = $bodyNodes->getNode(1);

        // Tests for {{ set koala = 2 }}
        // NOTE: Node zero in the array will be this
        $this->assertSame('set', $setNode->getNodeTag());
        $this->assertSame($name, $setNode->getNode('names')->getNode(0)->getAttribute('name'));
        $this->assertSame($caseChoice, $setNode->getNode('values')->getNode(0)->getAttribute('value'));

        // Tests for the {% switch %} block
        // NOTE: Node one in the array will be this
        $this->assertSame('switch', $switchNode->getNodeTag());
        $this->assertSame($name, $switchNode->getNode('value')->getAttribute('name'));

        // Test Cases
        $caseNodes = $switchNode->getNode('cases');

        // Case 1
        $this->assertSame(1, $caseNodes->getNode(0)->getAttribute('value'));
        $this->assertSame($caseTextOne, $caseNodes->getNode(1)->getAttribute('data'));

        // Case 2
        $this->assertSame(2, $caseNodes->getNode(2)->getAttribute('value'));
        $this->assertSame($caseTextTwo, $caseNodes->getNode(3)->getAttribute('data'));

        // Test Default
        $defaultNodes = $switchNode->getNode('default');
        $this->assertSame($caseTextDefault, $defaultNodes->getAttribute('data'));

        // Test compilation
        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $env = new Environment($loader, ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);

        $compiler = $this->getMockBuilder(Compiler::class)
            ->setMethods(['raw', 'subcompile', 'write'])
            ->setConstructorArgs([$env])
            ->getMock()
        ;
        $compiler
            ->expects($this->atLeast(3))
            ->method('raw')
            ->willReturnSelf()
        ;
        $compiler
            ->expects($this->atLeast(3))
            ->method('subcompile')
            ->willReturnSelf()
        ;
        $compiler
            ->expects($this->atLeast(1))
            ->method('write')
            ->willReturnSelf()
        ;
        /** @var Compiler $compiler */
        $switchNode->compile($compiler);
    }

    /**
     * @expectedException \Twig\Error\SyntaxError
     * @expectedExceptionMessageRegExp /Twig was looking for the following tags "case", "default", or "endswitch"/
     */
    public function testParseBad()
    {
        $name = 'koala';

        $streamTokens = [
            new Token(Token::BLOCK_START_TYPE, '', 2),
            new Token(Token::NAME_TYPE, 'switch', 2),
            new Token(Token::NAME_TYPE, $name, 2),
            new Token(Token::BLOCK_END_TYPE, '', 2),

            // Bad tag
            new Token(Token::TEXT_TYPE, '', 3),
            new Token(Token::BLOCK_START_TYPE, '', 3),
            new Token(Token::NAME_TYPE, 'slab', 3),
            new Token(Token::NUMBER_TYPE, 1, 3),
            new Token(Token::BLOCK_END_TYPE, '', 3),

            new Token(Token::BLOCK_START_TYPE, '', 90),
            new Token(Token::NAME_TYPE, 'endswitch', 91),
            new Token(Token::BLOCK_END_TYPE, '', 93),

            new Token(Token::EOF_TYPE, '', 99),
        ];
        $twigTokenStream = new TokenStream($streamTokens, new Source(null, 'clippy'));

        $parser = $this->getParser($twigTokenStream, new SwitchTokenParser());
        /** @var ModuleNode $nodeModule */
        $parser->parse($twigTokenStream);
    }
}
