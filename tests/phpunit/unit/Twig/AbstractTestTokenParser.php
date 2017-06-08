<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Node\Node;
use Twig\Parser;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

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
     * @param TokenStream         $tokenStream
     * @param AbstractTokenParser $testParser
     *
     * @return Parser
     */
    protected function getParser(TokenStream $tokenStream, AbstractTokenParser $testParser)
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
