<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Twig_TokenParser as TokenParser;
use Twig_Environment as Environment;
use Twig_LoaderInterface as LoaderInterface;
use Twig_Node as Node;
use Twig_Parser as Parser;
use Twig_TokenStream as TokenStream;

/**
 * Abstract TokenParser test base.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractTestTokenParser extends BoltUnitTest
{
    /**
     * @param TokenStream $tokenStream
     *
     * @return Parser
     */
    /**
     * @param TokenStream $tokenStream
     * @param TokenParser $testParser
     *
     * @return Parser
     */
    protected function getParser(TokenStream $tokenStream, TokenParser $testParser)
    {
        $env = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $parser = new Parser($env);
        $parser->setParent(new Node());
        $env->addTokenParser($testParser);

        $p = new \ReflectionProperty($parser, 'stream');
        $p->setAccessible(true);
        $p->setValue($parser, $tokenStream);

        return $parser;
    }
}
