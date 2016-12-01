<?php

namespace Bolt\Twig;

use Bolt;

/**
 * The class for Bolt' Twig tags, functions and filters.
 */
class TwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
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
            new \Twig_SimpleFunction('__',                 [Runtime\AdminHandler::class, 'trans'], $safe),
            new \Twig_SimpleFunction('backtrace',          [Runtime\UtilsHandler::class, 'printBacktrace']),
            new \Twig_SimpleFunction('buid',               [Runtime\AdminHandler::class, 'buid'], $safe),
            new \Twig_SimpleFunction('canonical',          [Runtime\RoutingHandler::class, 'canonical']),
            new \Twig_SimpleFunction('countwidgets',       [Runtime\WidgetHandler::class, 'countWidgets'], $safe),
            new \Twig_SimpleFunction('current',            [Runtime\RecordHandler::class, 'current']),
            new \Twig_SimpleFunction('data',               [Runtime\AdminHandler::class, 'addData']),
            new \Twig_SimpleFunction('dump',               [Runtime\UtilsHandler::class, 'printDump']),
            new \Twig_SimpleFunction('excerpt',            [Runtime\RecordHandler::class, 'excerpt'], $safe),
            new \Twig_SimpleFunction('fancybox',           [Runtime\ImageHandler::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFunction('fields',             [Runtime\RecordHandler::class, 'fields'], $env + $safe),
            new \Twig_SimpleFunction('file_exists',        [Runtime\UtilsHandler::class, 'fileExists'], $deprecated),
            new \Twig_SimpleFunction('firebug',            [Runtime\UtilsHandler::class, 'printFirebug']),
            new \Twig_SimpleFunction('first',              'twig_first', $env + $deprecated),
            new \Twig_SimpleFunction('getuser',            [Runtime\UserHandler::class, 'getUser']),
            new \Twig_SimpleFunction('getuserid',          [Runtime\UserHandler::class, 'getUserId']),
            new \Twig_SimpleFunction('getwidgets',         [Runtime\WidgetHandler::class, 'getWidgets'], $safe),
            new \Twig_SimpleFunction('haswidgets',         [Runtime\WidgetHandler::class, 'hasWidgets'], $safe),
            new \Twig_SimpleFunction('hattr',              [Runtime\AdminHandler::class, 'hattr'], $safe),
            new \Twig_SimpleFunction('hclass',             [Runtime\AdminHandler::class, 'hclass'], $safe),
            new \Twig_SimpleFunction('htmllang',           [Runtime\HtmlHandler::class, 'htmlLang']),
            new \Twig_SimpleFunction('image',              [Runtime\ImageHandler::class, 'image']),
            new \Twig_SimpleFunction('imageinfo',          [Runtime\ImageHandler::class, 'imageInfo']),
            new \Twig_SimpleFunction('isallowed',          [Runtime\UserHandler::class, 'isAllowed']),
            new \Twig_SimpleFunction('ischangelogenabled', [Runtime\AdminHandler::class, 'isChangelogEnabled'], $deprecated),
            new \Twig_SimpleFunction('ismobileclient',     [Runtime\HtmlHandler::class, 'isMobileClient']),
            new \Twig_SimpleFunction('last',               'twig_last', $env + $deprecated),
            new \Twig_SimpleFunction('link',               [Runtime\HtmlHandler::class, 'link'], $safe),
            new \Twig_SimpleFunction('listtemplates',      [Runtime\RecordHandler::class, 'listTemplates']),
            new \Twig_SimpleFunction('markdown',           [Runtime\HtmlHandler::class, 'markdown'], $safe),
            new \Twig_SimpleFunction('menu',               [Runtime\HtmlHandler::class, 'menu'], $env + $safe),
            new \Twig_SimpleFunction('popup',              [Runtime\ImageHandler::class, 'popup'], $safe),
            new \Twig_SimpleFunction('pager',              [Runtime\RecordHandler::class, 'pager'], $env),
            new \Twig_SimpleFunction('print',              [Runtime\UtilsHandler::class, 'printDump'], $deprecated + ['alternative' => 'dump']),
            new \Twig_SimpleFunction('randomquote',        [Runtime\AdminHandler::class, 'randomQuote'], $safe),
            new \Twig_SimpleFunction('redirect',           [Runtime\UtilsHandler::class, 'redirect'], $safe),
            new \Twig_SimpleFunction('request',            [Runtime\UtilsHandler::class, 'request']),
            new \Twig_SimpleFunction('showimage',          [Runtime\ImageHandler::class, 'showImage'], $safe),
            new \Twig_SimpleFunction('stack',              [Runtime\AdminHandler::class, 'stack']),
            new \Twig_SimpleFunction('thumbnail',          [Runtime\ImageHandler::class, 'thumbnail']),
            new \Twig_SimpleFunction('token',              [Runtime\UserHandler::class, 'token'], $deprecated + ['alternative' => 'csrf_token']),
            new \Twig_SimpleFunction('trimtext',           [Runtime\RecordHandler::class, 'trim'], $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFunction('unique',             [Runtime\ArrayHandler::class, 'unique'], $safe),
            new \Twig_SimpleFunction('widgets',            [Runtime\WidgetHandler::class, 'widgets'], $safe),
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
            new \Twig_SimpleFilter('__',             [Runtime\AdminHandler::class, 'trans']),
            new \Twig_SimpleFilter('current',        [Runtime\RecordHandler::class, 'current']),
            new \Twig_SimpleFilter('editable',       [Runtime\HtmlHandler::class, 'editable'], $safe),
            new \Twig_SimpleFilter('excerpt',        [Runtime\RecordHandler::class, 'excerpt'], $safe),
            new \Twig_SimpleFilter('fancybox',       [Runtime\ImageHandler::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFilter('image',          [Runtime\ImageHandler::class, 'image']),
            new \Twig_SimpleFilter('imageinfo',      [Runtime\ImageHandler::class, 'imageInfo']),
            new \Twig_SimpleFilter('json_decode',    [Runtime\TextHandler::class, 'jsonDecode']),
            new \Twig_SimpleFilter('localdate',      [Runtime\TextHandler::class, 'localeDateTime'], $safe + $deprecated + ['alternative' => 'localedatetime']),
            new \Twig_SimpleFilter('localedatetime', [Runtime\TextHandler::class, 'localeDateTime'], $safe),
            new \Twig_SimpleFilter('loglevel',       [Runtime\AdminHandler::class, 'logLevel']),
            new \Twig_SimpleFilter('markdown',       [Runtime\HtmlHandler::class, 'markdown'], $safe),
            new \Twig_SimpleFilter('order',          [Runtime\ArrayHandler::class, 'order']),
            new \Twig_SimpleFilter('popup',          [Runtime\ImageHandler::class, 'popup'], $safe),
            new \Twig_SimpleFilter('preg_replace',   [Runtime\TextHandler::class, 'pregReplace']),
            new \Twig_SimpleFilter('safestring',     [Runtime\TextHandler::class, 'safeString'], $safe),
            new \Twig_SimpleFilter('selectfield',    [Runtime\RecordHandler::class, 'selectField']),
            new \Twig_SimpleFilter('showimage',      [Runtime\ImageHandler::class, 'showImage'], $safe),
            new \Twig_SimpleFilter('shuffle',        [Runtime\ArrayHandler::class, 'shuffle']),
            new \Twig_SimpleFilter('shy',            [Runtime\HtmlHandler::class, 'shy'], $safe),
            new \Twig_SimpleFilter('slug',           [Runtime\TextHandler::class, 'slug']),
            new \Twig_SimpleFilter('thumbnail',      [Runtime\ImageHandler::class, 'thumbnail']),
            new \Twig_SimpleFilter('trimtext',       [Runtime\RecordHandler::class, 'trim'], $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFilter('tt',             [Runtime\HtmlHandler::class, 'decorateTT'], $safe),
            new \Twig_SimpleFilter('twig',           [Runtime\HtmlHandler::class, 'twig'], $safe),
            new \Twig_SimpleFilter('ucfirst',        'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
            new \Twig_SimpleFilter('ymllink',        [Runtime\AdminHandler::class, 'ymllink'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json',      [Runtime\TextHandler::class, 'testJson']),
            new \Twig_SimpleTest('stackable', [Runtime\AdminHandler::class, 'testStackable']),
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
