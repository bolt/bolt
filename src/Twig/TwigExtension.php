<?php

namespace Bolt\Twig;

use Bolt;
use Silex;

/**
 * The class for Bolt' Twig tags, functions and filters.
 */
class TwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var \Silex\Application */
    private $app;

    /** @var boolean */
    private $safe;

    /** @var \Pimple */
    private $handlers;

    /**
     * @param \Silex\Application $app
     * @param \Pimple            $handlers
     * @param boolean            $safe
     */
    public function __construct(Silex\Application $app, \Pimple $handlers, $safe)
    {
        $this->app      = $app;
        $this->handlers = $handlers;
        $this->safe     = $safe;
    }

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

    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];
        $env  = ['needs_environment' => true];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('__',                 [$this, 'trans'],       $safe),
            new \Twig_SimpleFunction('backtrace',          [$this, 'printBacktrace']),
            new \Twig_SimpleFunction('buid',               [$this, 'buid'],        $safe),
            new \Twig_SimpleFunction('canonical',          [$this, 'canonical']),
            new \Twig_SimpleFunction('countwidgets',       [$this, 'countWidgets'],  $safe),
            new \Twig_SimpleFunction('current',            [$this, 'current']),
            new \Twig_SimpleFunction('data',               [$this, 'addData']),
            new \Twig_SimpleFunction('dump',               [$this, 'printDump']),
            new \Twig_SimpleFunction('excerpt',            [$this, 'excerpt'],     $safe),
            new \Twig_SimpleFunction('fancybox',           [$this, 'popup'],       $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFunction('fields',             [$this, 'fields'],      $env + $safe),
            new \Twig_SimpleFunction('file_exists',        [$this, 'fileExists'],  $deprecated),
            new \Twig_SimpleFunction('firebug',            [$this, 'printFirebug']),
            new \Twig_SimpleFunction('first',              'twig_first',           $env + $deprecated),
            new \Twig_SimpleFunction('getuser',            [$this, 'getUser']),
            new \Twig_SimpleFunction('getuserid',          [$this, 'getUserId']),
            new \Twig_SimpleFunction('getwidgets',         [$this, 'getWidgets'],  $safe),
            new \Twig_SimpleFunction('haswidgets',         [$this, 'hasWidgets'],  $safe),
            new \Twig_SimpleFunction('hattr',              [$this, 'hattr'],       $safe),
            new \Twig_SimpleFunction('hclass',             [$this, 'hclass'],      $safe),
            new \Twig_SimpleFunction('htmllang',           [$this, 'htmlLang']),
            new \Twig_SimpleFunction('image',              [$this, 'image']),
            new \Twig_SimpleFunction('imageinfo',          [$this, 'imageInfo']),
            new \Twig_SimpleFunction('isallowed',          [$this, 'isAllowed']),
            new \Twig_SimpleFunction('ischangelogenabled', [$this, 'isChangelogEnabled'], $deprecated),
            new \Twig_SimpleFunction('ismobileclient',     [$this, 'isMobileClient']),
            new \Twig_SimpleFunction('last',               'twig_last',            $env + $deprecated),
            new \Twig_SimpleFunction('link',               [$this, 'link'],        $safe),
            new \Twig_SimpleFunction('listtemplates',      [$this, 'listTemplates']),
            new \Twig_SimpleFunction('markdown',           [$this, 'markdown'],    $safe),
            new \Twig_SimpleFunction('menu',               [$this, 'menu'],        $env + $safe),
            new \Twig_SimpleFunction('pager',              [$this, 'pager'],       $env),
            new \Twig_SimpleFunction('popup',              [$this, 'popup'],       $safe),
            new \Twig_SimpleFunction('print',              [$this, 'printDump'],   $deprecated + ['alternative' => 'dump']),
            new \Twig_SimpleFunction('randomquote',        [$this, 'randomQuote'], $safe),
            new \Twig_SimpleFunction('redirect',           [$this, 'redirect'],    $safe),
            new \Twig_SimpleFunction('request',            [$this, 'request']),
            new \Twig_SimpleFunction('showimage',          [$this, 'showImage'],   $safe),
            new \Twig_SimpleFunction('stack',              [$this, 'stack']),
            new \Twig_SimpleFunction('thumbnail',          [$this, 'thumbnail']),
            new \Twig_SimpleFunction('token',              [$this, 'token'],       $deprecated + ['alternative' => 'csrf_token']),
            new \Twig_SimpleFunction('trimtext',           [$this, 'trim'],        $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFunction('unique',             [$this, 'unique'],      $safe),
            new \Twig_SimpleFunction('widgets',            [$this, 'widgets'],      $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getFilters()
    {
        $safe = ['is_safe' => ['html']];
        $env  = ['needs_environment' => true];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFilter('__',             [$this, 'trans']),
            new \Twig_SimpleFilter('current',        [$this, 'current']),
            new \Twig_SimpleFilter('editable',       [$this, 'editable'],          $safe),
            new \Twig_SimpleFilter('excerpt',        [$this, 'excerpt'],           $safe),
            new \Twig_SimpleFilter('fancybox',       [$this, 'popup'],             $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFilter('image',          [$this, 'image']),
            new \Twig_SimpleFilter('imageinfo',      [$this, 'imageInfo']),
            new \Twig_SimpleFilter('json_decode',    [$this, 'jsonDecode']),
            new \Twig_SimpleFilter('localdate',      [$this, 'localeDateTime'],    $safe + $deprecated + ['alternative' => 'localedatetime']),
            new \Twig_SimpleFilter('localedatetime', [$this, 'localeDateTime'],    $safe),
            new \Twig_SimpleFilter('loglevel',       [$this, 'logLevel']),
            new \Twig_SimpleFilter('markdown',       [$this, 'markdown'],          $safe),
            new \Twig_SimpleFilter('order',          [$this, 'order']),
            new \Twig_SimpleFilter('popup',          [$this, 'popup'],             $safe),
            new \Twig_SimpleFilter('preg_replace',   [$this, 'pregReplace']),
            new \Twig_SimpleFilter('safestring',     [$this, 'safeString'],        $safe),
            new \Twig_SimpleFilter('selectfield',    [$this, 'selectField']),
            new \Twig_SimpleFilter('showimage',      [$this, 'showImage'],         $safe),
            new \Twig_SimpleFilter('shuffle',        [$this, 'shuffle']),
            new \Twig_SimpleFilter('shy',            [$this, 'shy'],               $safe),
            new \Twig_SimpleFilter('slug',           [$this, 'slug']),
            new \Twig_SimpleFilter('thumbnail',      [$this, 'thumbnail']),
            new \Twig_SimpleFilter('trimtext',       [$this, 'trim'],              $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFilter('tt',             [$this, 'decorateTT'],        $safe),
            new \Twig_SimpleFilter('twig',           [$this, 'twig'],              $safe + $deprecated + ['alternative' => 'template_from_string']),
            new \Twig_SimpleFilter('ucfirst',        'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
            new \Twig_SimpleFilter('ymllink',        [$this, 'ymllink'],           $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json', [$this, 'testJson']),
            new \Twig_SimpleTest('stackable', [$this, 'testStackable']),
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
