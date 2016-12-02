<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\SetcontentTokenParser;
use Bolt\Twig\SwitchTokenParser;

use Bolt;

/**
 * Bolt base Twig functionality and definitions.
 */
class BoltExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var boolean */
    private $safe;

    /**
     * @param boolean $safe
     */
    public function __construct($safe)
    {
        $this->safe = $safe;
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
     * @return \Bolt\Storage\EntityManager
     */
    public function getStorage()
    {
        return $this->app['storage'];
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
            new \Twig_SimpleFunction('first', 'twig_first', $env + $deprecated),
            new \Twig_SimpleFunction('last',  'twig_last', $env + $deprecated),
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
            new \Twig_SimpleFilter('ucfirst', 'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
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
            'bolt_stable'  => Bolt\Version::isStable(),
            'frontend'     => null,
            'backend'      => null,
            'async'        => null,
            'paths'        => $this->app['resources']->getPaths(),
            'theme'        => null,
            'user'         => null,
            'users'        => null,
            'config'       => $this->app['config'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        $parsers = [];
        if (!$this->safe) {
            $parsers[] = new SetcontentTokenParser();
            $parsers[] = new SwitchTokenParser();
        }

        return $parsers;
    }
}
