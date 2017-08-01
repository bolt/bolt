<?php

namespace Bolt\Tests\Twig;

use Bolt\Twig\SetcontentNode;
use Bolt\Twig\SetcontentTokenParser;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * Class to test Twig {{ setcontent }} token classes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SetcontentTokenParserTest extends AbstractTestTokenParser
{
    public function testClass()
    {
        $setContentParser = new SetcontentTokenParser();
        $this->assertInstanceOf(AbstractTokenParser::class, $setContentParser);
    }

    public function testGetTag()
    {
        $setContentParser = new SetcontentTokenParser();
        $this->assertSame('setcontent', $setContentParser->getTag());
    }

    public function providerParse()
    {
        return [
            'v3 parser' => [true],
            'v2 parser' => [false],
        ];
    }

    /**
     * @dataProvider providerParse
     */
    public function testParse($legacy)
    {
        $name = 'koala';
        $where = "{ status: 'published', datepublish: '> 2012-06-14', taxonomy: 'main|||meta|||other' }";
        $contentType = 'pages';
        $limit = 5;
        $streamTokens = [
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'setcontent', 1),

            new Token(Token::NAME_TYPE, $name, 1),
            new Token(Token::OPERATOR_TYPE, '=', 2),
            new Token(Token::STRING_TYPE, $contentType, 3),

            new Token(Token::NAME_TYPE, 'where', 4),
            new Token(Token::STRING_TYPE, $where, 5),

            new Token(Token::NAME_TYPE, 'limit', 6),
            new Token(Token::NUMBER_TYPE, $limit, 7),

            new Token(Token::NAME_TYPE, 'order', 8),
            new Token(Token::STRING_TYPE, '-name', 9),

            new Token(Token::NAME_TYPE, 'orderby', 10),
            new Token(Token::STRING_TYPE, 'title', 11),

            new Token(Token::NAME_TYPE, 'paging', 12),
            new Token(Token::NAME_TYPE, 'allowpaging', 13),

            new Token(Token::NAME_TYPE, 'printquery', 14),

            new Token(Token::NAME_TYPE, 'returnsingle', 15),

            new Token(Token::NAME_TYPE, 'nohydrate', 16),

            new Token(Token::BLOCK_END_TYPE, '', 98),
            new Token(Token::EOF_TYPE, '', 99),
        ];
        $twigTokenStream = new TokenStream($streamTokens, new Source(null, 'clippy'));

        $parser = $this->getParser($twigTokenStream, new SetcontentTokenParser($legacy));
        /** @var ModuleNode $setContentNode */
        $setContentNode = $parser->parse($twigTokenStream);
        /** @var BodyNode $bodyNodes */
        $bodyNodes = $setContentNode->getNode('body');

        /** @var SetcontentNode $bodyNode */
        foreach ($bodyNodes->getIterator() as $bodyNode) {
            $this->assertSame($name, $bodyNode->getAttribute('name'));
            $this->assertSame($where, $bodyNode->getNode('wherearguments')->getAttribute('value'));

            $this->assertSame($contentType, $bodyNode->getAttribute('contenttype')->getAttribute('value'));

            $nodes = $bodyNode->getAttribute('arguments')->getKeyValuePairs();

            $this->assertSame('limit', $nodes[0]['key']->getAttribute('value'));
            $this->assertSame($limit, $nodes[0]['value']->getAttribute('value'));

            $this->assertSame('order', $nodes[1]['key']->getAttribute('value'));
            $this->assertSame('-name', $nodes[1]['value']->getAttribute('value'));

            $this->assertSame('order', $nodes[2]['key']->getAttribute('value'));

            $this->assertSame('title', $nodes[2]['value']->getAttribute('value'));

            $this->assertSame('paging', $nodes[3]['key']->getAttribute('value'));
            $this->assertTrue($nodes[3]['value']->getAttribute('value'));

            $this->assertSame('paging', $nodes[4]['key']->getAttribute('value'));
            $this->assertTrue($nodes[4]['value']->getAttribute('value'));

            $this->assertSame('printquery', $nodes[5]['key']->getAttribute('value'));
            $this->assertTrue($nodes[5]['value']->getAttribute('value'));

            $this->assertSame('returnsingle', $nodes[6]['key']->getAttribute('value'));
            $this->assertTrue($nodes[6]['value']->getAttribute('value'));

            $this->assertSame('hydrate', $nodes[7]['key']->getAttribute('value'));
            $this->assertFalse($nodes[7]['value']->getAttribute('value'));
        }

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
        $setContentNode->compile($compiler);
    }
}
