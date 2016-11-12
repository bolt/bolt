<?php
namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\SetcontentTokenParser;
use Twig_Compiler;
use Twig_Environment;
use Twig_ExpressionParser;
use Twig_Parser;
use Twig_Token;
use Twig_TokenStream;

/**
 * Class to test Twig {{ setcontent }} token classes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SetcontentTest extends BoltUnitTest
{
    public function testClass()
    {
        $setContentParser = new SetcontentTokenParser();
        $this->assertInstanceOf('Twig_TokenParser', $setContentParser);
    }

    public function testGetTag()
    {
        $setContentParser = new SetcontentTokenParser();
        $this->assertSame('setcontent', $setContentParser->getTag());
    }

    public function testParse()
    {
        $app = $this->getApp();

        $name = 'koala';
        $where = "{ status: 'published', datepublish: '> 2012-06-14', taxonomy: 'main|||meta|||other' }";
        $contentType = 'pages';
        $limit = 5;

        $streamTokens = [
            new Twig_Token(Twig_Token::NAME_TYPE, $name, 1),
            new Twig_Token(Twig_Token::OPERATOR_TYPE, '=', 2),
            new Twig_Token(Twig_Token::STRING_TYPE, $contentType, 3),

            new Twig_Token(Twig_Token::NAME_TYPE, 'where', 4),
            new Twig_Token(Twig_Token::STRING_TYPE, $where, 5),

            new Twig_Token(Twig_Token::NAME_TYPE, 'limit', 6),
            new Twig_Token(Twig_Token::NUMBER_TYPE, $limit, 7),

            new Twig_Token(Twig_Token::NAME_TYPE, 'order', 8),
            new Twig_Token(Twig_Token::STRING_TYPE, '-name', 9),

            new Twig_Token(Twig_Token::NAME_TYPE, 'orderby', 10),
            new Twig_Token(Twig_Token::STRING_TYPE, 'title', 11),

            new Twig_Token(Twig_Token::NAME_TYPE, 'paging', 12),
            new Twig_Token(Twig_Token::NAME_TYPE, 'allowpaging', 13),

            new Twig_Token(Twig_Token::NAME_TYPE, 'printquery', 14),

            new Twig_Token(Twig_Token::NAME_TYPE, 'returnsingle', 15),

            new Twig_Token(Twig_Token::NAME_TYPE, 'nohydrate', 16),

            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 98),
            new Twig_Token(Twig_Token::EOF_TYPE, '', 99),
        ];
        $twigTokenStream = new Twig_TokenStream($streamTokens, 'clippy.twig');

        $twigParser = new TwigParserMock($app['twig']);
        $twigParser->setStream($twigTokenStream);

        $env = $app['twig'];
        $expression = new Twig_ExpressionParser($twigParser, $env);
        $twigParser->setExpressionParser($expression);

        $setContentParser = new SetcontentTokenParser();
        $setContentParser->setParser($twigParser);

        $token = new Twig_Token(Twig_Token::NAME_TYPE, 'setcontent', 1);

        $result = $setContentParser->parse($token);

        $this->assertSame($where, $result->getNode('wherearguments')->getAttribute('value'));
        $this->assertSame($name, $result->getAttribute('name'));
        $this->assertSame($contentType, $result->getAttribute('contenttype')->getAttribute('value'));

        $nodes = $result->getAttribute('arguments')->getKeyValuePairs();

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

        $env = new Twig_Environment($this->getMock('Twig_LoaderInterface'));
        $compiler = $this->getMock('Twig_Compiler', ['addDebugInfo', 'raw', 'subcompile', 'write'], [$env]);
        $compiler
            ->expects($this->once())
            ->method('addDebugInfo')
            ->willReturnSelf()
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
            ->expects($this->atLeast(3))
            ->method('write')
            ->willReturnSelf()
        ;

        $result->compile($compiler);
    }
}

class TwigParserMock extends Twig_Parser
{
    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
    }

    public function setExpressionParser(Twig_ExpressionParser $expression)
    {
        $this->expressionParser = $expression;
    }

    public function setStream(Twig_TokenStream $stream)
    {
        $this->stream = $stream;
    }
}
