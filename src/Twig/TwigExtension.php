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
            new \Twig_SimpleFunction('__',                 [Handler\AdminHandler::class, 'trans'], $safe),
            new \Twig_SimpleFunction('backtrace',          [Handler\UtilsHandler::class, 'printBacktrace']),
            new \Twig_SimpleFunction('buid',               [Handler\AdminHandler::class, 'buid'], $safe),
            new \Twig_SimpleFunction('canonical',          [Handler\RoutingHandler::class, 'canonical']),
            new \Twig_SimpleFunction('countwidgets',       [Handler\WidgetHandler::class, 'countWidgets'], $safe),
            new \Twig_SimpleFunction('current',            [Handler\RecordHandler::class, 'current']),
            new \Twig_SimpleFunction('data',               [Handler\AdminHandler::class, 'addData']),
            new \Twig_SimpleFunction('dump',               [Handler\UtilsHandler::class, 'printDump']),
            new \Twig_SimpleFunction('excerpt',            [Handler\RecordHandler::class, 'excerpt'], $safe),
            new \Twig_SimpleFunction('fancybox',           [Handler\ImageHandler::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFunction('fields',             [Handler\RecordHandler::class, 'fields'], $env + $safe),
            new \Twig_SimpleFunction('file_exists',        [Handler\UtilsHandler::class, 'fileExists'], $deprecated),
            new \Twig_SimpleFunction('firebug',            [Handler\UtilsHandler::class, 'printFirebug']),
            new \Twig_SimpleFunction('first',              'twig_first', $env + $deprecated),
            new \Twig_SimpleFunction('getuser',            [Handler\UserHandler::class, 'getUser']),
            new \Twig_SimpleFunction('getuserid',          [Handler\UserHandler::class, 'getUserId']),
            new \Twig_SimpleFunction('getwidgets',         [Handler\WidgetHandler::class, 'getWidgets'], $safe),
            new \Twig_SimpleFunction('haswidgets',         [Handler\WidgetHandler::class, 'hasWidgets'], $safe),
            new \Twig_SimpleFunction('hattr',              [Handler\AdminHandler::class, 'hattr'], $safe),
            new \Twig_SimpleFunction('hclass',             [Handler\AdminHandler::class, 'hclass'], $safe),
            new \Twig_SimpleFunction('htmllang',           [Handler\HtmlHandler::class, 'htmlLang']),
            new \Twig_SimpleFunction('image',              [Handler\ImageHandler::class, 'image']),
            new \Twig_SimpleFunction('imageinfo',          [Handler\ImageHandler::class, 'imageInfo']),
            new \Twig_SimpleFunction('isallowed',          [Handler\UserHandler::class, 'isAllowed']),
            new \Twig_SimpleFunction('ischangelogenabled', [Handler\AdminHandler::class, 'isChangelogEnabled'], $deprecated),
            new \Twig_SimpleFunction('ismobileclient',     [Handler\HtmlHandler::class, 'isMobileClient']),
            new \Twig_SimpleFunction('last',               'twig_last', $env + $deprecated),
            new \Twig_SimpleFunction('link',               [Handler\HtmlHandler::class, 'link'], $safe),
            new \Twig_SimpleFunction('listtemplates',      [Handler\RecordHandler::class, 'listTemplates']),
            new \Twig_SimpleFunction('markdown',           [Handler\HtmlHandler::class, 'markdown'], $safe),
            new \Twig_SimpleFunction('menu',               [Handler\HtmlHandler::class, 'menu'], $env + $safe),
            new \Twig_SimpleFunction('popup',              [Handler\ImageHandler::class, 'popup'], $safe),
            new \Twig_SimpleFunction('pager',              [Handler\RecordHandler::class, 'pager'], $env),
            new \Twig_SimpleFunction('print',              [Handler\UtilsHandler::class, 'printDump'], $deprecated + ['alternative' => 'dump']),
            new \Twig_SimpleFunction('randomquote',        [Handler\AdminHandler::class, 'randomQuote'], $safe),
            new \Twig_SimpleFunction('redirect',           [Handler\UtilsHandler::class, 'redirect'], $safe),
            new \Twig_SimpleFunction('request',            [Handler\UtilsHandler::class, 'request']),
            new \Twig_SimpleFunction('showimage',          [Handler\ImageHandler::class, 'showImage'], $safe),
            new \Twig_SimpleFunction('stack',              [Handler\AdminHandler::class, 'stack']),
            new \Twig_SimpleFunction('thumbnail',          [Handler\ImageHandler::class, 'thumbnail']),
            new \Twig_SimpleFunction('token',              [Handler\UserHandler::class, 'token'], $deprecated + ['alternative' => 'csrf_token']),
            new \Twig_SimpleFunction('trimtext',           [Handler\RecordHandler::class, 'trim'], $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFunction('unique',             [Handler\ArrayHandler::class, 'unique'], $safe),
            new \Twig_SimpleFunction('widgets',            [Handler\WidgetHandler::class, 'widgets'], $safe),
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
            new \Twig_SimpleFilter('__',             [Handler\AdminHandler::class, 'trans']),
            new \Twig_SimpleFilter('current',        [Handler\RecordHandler::class, 'current']),
            new \Twig_SimpleFilter('editable',       [Handler\HtmlHandler::class, 'editable'], $safe),
            new \Twig_SimpleFilter('excerpt',        [Handler\RecordHandler::class, 'excerpt'], $safe),
            new \Twig_SimpleFilter('fancybox',       [Handler\ImageHandler::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFilter('image',          [Handler\ImageHandler::class, 'image']),
            new \Twig_SimpleFilter('imageinfo',      [Handler\ImageHandler::class, 'imageInfo']),
            new \Twig_SimpleFilter('json_decode',    [Handler\TextHandler::class, 'jsonDecode']),
            new \Twig_SimpleFilter('localdate',      [Handler\TextHandler::class, 'localeDateTime'], $safe + $deprecated + ['alternative' => 'localedatetime']),
            new \Twig_SimpleFilter('localedatetime', [Handler\TextHandler::class, 'localeDateTime'], $safe),
            new \Twig_SimpleFilter('loglevel',       [Handler\AdminHandler::class, 'logLevel']),
            new \Twig_SimpleFilter('markdown',       [Handler\HtmlHandler::class, 'markdown'], $safe),
            new \Twig_SimpleFilter('order',          [Handler\ArrayHandler::class, 'order']),
            new \Twig_SimpleFilter('popup',          [Handler\ImageHandler::class, 'popup'], $safe),
            new \Twig_SimpleFilter('preg_replace',   [Handler\TextHandler::class, 'pregReplace']),
            new \Twig_SimpleFilter('safestring',     [Handler\TextHandler::class, 'safeString'], $safe),
            new \Twig_SimpleFilter('selectfield',    [Handler\RecordHandler::class, 'selectField']),
            new \Twig_SimpleFilter('showimage',      [Handler\ImageHandler::class, 'showImage'], $safe),
            new \Twig_SimpleFilter('shuffle',        [Handler\ArrayHandler::class, 'shuffle']),
            new \Twig_SimpleFilter('shy',            [Handler\HtmlHandler::class, 'shy'], $safe),
            new \Twig_SimpleFilter('slug',           [Handler\TextHandler::class, 'slug']),
            new \Twig_SimpleFilter('thumbnail',      [Handler\ImageHandler::class, 'thumbnail']),
            new \Twig_SimpleFilter('trimtext',       [Handler\RecordHandler::class, 'trim'], $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFilter('tt',             [Handler\HtmlHandler::class, 'decorateTT'], $safe),
            new \Twig_SimpleFilter('twig',           [Handler\HtmlHandler::class, 'twig'], $safe),
            new \Twig_SimpleFilter('ucfirst',        'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
            new \Twig_SimpleFilter('ymllink',        [Handler\AdminHandler::class, 'ymllink'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json', [Handler\TextHandler::class, 'testJson']),
            new \Twig_SimpleTest('stackable', [Handler\AdminHandler::class, 'testStackable']),
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
