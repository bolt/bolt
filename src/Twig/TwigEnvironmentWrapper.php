<?php

namespace Bolt\Twig;

use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\LoaderInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TokenParser\TokenParserInterface;
use Twig\TokenStream;
use Twig_CompilerInterface as CompilerInterface;
use Twig_LexerInterface as LexerInterface;
use Twig_NodeInterface as NodeInterface;
use Twig_ParserInterface as ParserInterface;

/**
 * Base class for wrapping twig environment.
 *
 * @deprecated since 3.3, will be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class TwigEnvironmentWrapper extends Environment
{
    protected $env;

    /**
     * Constructor.
     *
     * @param Environment $env
     */
    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseTemplateClass()
    {
        return $this->env->getBaseTemplateClass();
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseTemplateClass($class)
    {
        $this->env->setBaseTemplateClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function enableDebug()
    {
        $this->env->enableDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function disableDebug()
    {
        $this->env->disableDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return $this->env->isDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function enableAutoReload()
    {
        $this->env->enableAutoReload();
    }

    /**
     * {@inheritdoc}
     */
    public function disableAutoReload()
    {
        $this->env->disableAutoReload();
    }

    /**
     * {@inheritdoc}
     */
    public function isAutoReload()
    {
        return $this->env->isAutoReload();
    }

    /**
     * {@inheritdoc}
     */
    public function enableStrictVariables()
    {
        $this->env->enableStrictVariables();
    }

    /**
     * {@inheritdoc}
     */
    public function disableStrictVariables()
    {
        $this->env->disableStrictVariables();
    }

    /**
     * {@inheritdoc}
     */
    public function isStrictVariables()
    {
        return $this->env->isStrictVariables();
    }

    /**
     * {@inheritdoc}
     */
    public function getCache($original = true)
    {
        return $this->env->getCache($original);
    }

    /**
     * {@inheritdoc}
     */
    public function setCache($cache)
    {
        $this->env->setCache($cache);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheFilename($name)
    {
        return $this->env->getCacheFilename($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateClass($name, $index = null)
    {
        return $this->env->getTemplateClass($name, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateClassPrefix()
    {
        return $this->env->getTemplateClassPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $context = [])
    {
        return $this->env->render($name, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function display($name, array $context = [])
    {
        $this->env->display($name, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function load($name)
    {
        return $this->env->load($name);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTemplate($name, $index = null)
    {
        return $this->env->loadTemplate($name, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemplate($template)
    {
        return $this->env->createTemplate($template);
    }

    /**
     * {@inheritdoc}
     */
    public function isTemplateFresh($name, $time)
    {
        return $this->env->isTemplateFresh($name, $time);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveTemplate($names)
    {
        return $this->env->resolveTemplate($names);
    }

    /**
     * {@inheritdoc}
     */
    public function clearTemplateCache()
    {
        $this->env->clearTemplateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function clearCacheFiles()
    {
        $this->env->clearCacheFiles();
    }

    /**
     * {@inheritdoc}
     */
    public function getLexer()
    {
        return $this->env->getLexer();
    }

    /**
     * {@inheritdoc}
     */
    public function setLexer(LexerInterface $lexer)
    {
        $this->env->setLexer($lexer);
    }

    /**
     * {@inheritdoc}
     */
    public function tokenize($source, $name = null)
    {
        return $this->env->tokenize($source, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getParser()
    {
        return $this->env->getParser();
    }

    /**
     * {@inheritdoc}
     */
    public function setParser(ParserInterface $parser)
    {
        $this->env->setParser($parser);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(TokenStream $stream)
    {
        return $this->env->parse($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompiler()
    {
        return $this->env->getCompiler();
    }

    /**
     * {@inheritdoc}
     */
    public function setCompiler(CompilerInterface $compiler)
    {
        $this->env->setCompiler($compiler);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(NodeInterface $node)
    {
        return $this->env->compile($node);
    }

    /**
     * {@inheritdoc}
     */
    public function compileSource($source, $name = null)
    {
        return $this->env->compileSource($source, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->env->setLoader($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function getLoader()
    {
        return $this->env->getLoader();
    }

    /**
     * {@inheritdoc}
     */
    public function setCharset($charset)
    {
        $this->env->setCharset($charset);
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
        return $this->env->getCharset();
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime()
    {
        $this->env->initRuntime();
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($class)
    {
        return $this->env->hasExtension($class);
    }

    /**
     * {@inheritdoc}
     */
    public function addRuntimeLoader(RuntimeLoaderInterface $loader)
    {
        $this->env->addRuntimeLoader($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($class)
    {
        return $this->env->getExtension($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getRuntime($class)
    {
        return $this->env->getRuntime($class);
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->env->addExtension($extension);
    }

    /**
     * {@inheritdoc}
     */
    public function removeExtension($name)
    {
        $this->env->removeExtension($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensions(array $extensions)
    {
        $this->env->setExtensions($extensions);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return $this->env->getExtensions();
    }

    /**
     * {@inheritdoc}
     */
    public function addTokenParser(TokenParserInterface $parser)
    {
        $this->env->addTokenParser($parser);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return $this->env->getTokenParsers();
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return $this->env->getTags();
    }

    /**
     * {@inheritdoc}
     */
    public function addNodeVisitor(NodeVisitorInterface $visitor)
    {
        $this->env->addNodeVisitor($visitor);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors()
    {
        return $this->env->getNodeVisitors();
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter($name, $filter = null)
    {
        $this->env->addFilter($name, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter($name)
    {
        return $this->env->getFilter($name);
    }

    /**
     * {@inheritdoc}
     */
    public function registerUndefinedFilterCallback($callable)
    {
        $this->env->registerUndefinedFilterCallback($callable);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return $this->env->getFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function addTest($name, $test = null)
    {
        $this->env->addTest($name, $test);
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return $this->env->getTests();
    }

    /**
     * {@inheritdoc}
     */
    public function getTest($name)
    {
        return $this->env->getTest($name);
    }

    /**
     * {@inheritdoc}
     */
    public function addFunction($name, $function = null)
    {
        $this->env->addFunction($name, $function);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunction($name)
    {
        return $this->env->getFunction($name);
    }

    /**
     * {@inheritdoc}
     */
    public function registerUndefinedFunctionCallback($callable)
    {
        $this->env->registerUndefinedFunctionCallback($callable);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->env->getFunctions();
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal($name, $value)
    {
        $this->env->addGlobal($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return $this->env->getGlobals();
    }

    /**
     * {@inheritdoc}
     */
    public function mergeGlobals(array $context)
    {
        return $this->env->mergeGlobals($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getUnaryOperators()
    {
        return $this->env->getUnaryOperators();
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryOperators()
    {
        return $this->env->getBinaryOperators();
    }

    /**
     * {@inheritdoc}
     */
    public function computeAlternatives($name, $items)
    {
        return $this->env->computeAlternatives($name, $items);
    }
}
