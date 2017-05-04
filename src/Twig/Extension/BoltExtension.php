<?php

namespace Bolt\Twig\Extension;

use Bolt;
use Bolt\Config;
use Bolt\Configuration\PathsProxy;
use Bolt\Storage\EntityManagerInterface;
use Bolt\Storage\Query\Query;
use Bolt\Twig\SetcontentTokenParser;
use Bolt\Twig\SwitchTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Bolt base Twig functionality and definitions.
 */
class BoltExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Config */
    private $config;
    /** @var PathsProxy */
    private $paths;
    /** @var Query */
    private $queryEngine;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $em
     * @param Config                 $config
     * @param PathsProxy             $paths
     * @param Query                  $query
     */
    public function __construct(EntityManagerInterface $em, Config $config, PathsProxy $paths, Query $query)
    {
        $this->em = $em;
        $this->config = $config;
        $this->paths = $paths;
        $this->queryEngine = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Bolt';
    }

    /**
     * Used by setcontent tag.
     *
     * @return EntityManagerInterface
     */
    public function getStorage()
    {
        return $this->em;
    }

    /**
     * Used by new version of setcontent tag.
     *
     * @return Query
     */
    public function getQueryEngine()
    {
        return $this->queryEngine;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $env  = ['needs_environment' => true];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('first', 'twig_first', $env + $deprecated),
            new TwigFunction('last',  'twig_last', $env + $deprecated),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $env  = ['needs_environment' => true];
        $deprecated = ['deprecated' => true];

        return [
            new TwigFilter('ucfirst', 'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * As of Twig 2.x, the ability to register a global variable after runtime
     * or the extensions have been initialized will not be possible any longer,
     * but changing the value of an already registered global is possible.
     */
    public function getGlobals()
    {
        return [
            'bolt_name'    => Bolt\Version::name(),
            'bolt_version' => Bolt\Version::VERSION,
            'frontend'     => null,
            'backend'      => null,
            'async'        => null,
            'paths'        => $this->paths,
            'theme'        => null,
            'user'         => null,
            'users'        => null,
            'config'       => $this->config,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        $parserVersion = $this->config->get('general/compatibility/setcontent_version', 1);
        $parsers = [
            new SwitchTokenParser(),
            new SetcontentTokenParser($parserVersion),
        ];

        return $parsers;
    }
}
