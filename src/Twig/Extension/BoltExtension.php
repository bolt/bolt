<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Bolt\Twig\SetcontentTokenParser;
use Bolt\Twig\SwitchTokenParser;

use Bolt;

/**
 * The class for Bolt' Twig tags, functions and filters.
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
            new \Twig_SimpleFunction('__',                 [Runtime\AdminRuntime::class, 'trans'], $safe),
            new \Twig_SimpleFunction('backtrace',          [Runtime\UtilsRuntime::class, 'printBacktrace']),
            new \Twig_SimpleFunction('buid',               [Runtime\AdminRuntime::class, 'buid'], $safe),
            new \Twig_SimpleFunction('canonical',          [Runtime\RoutingRuntime::class, 'canonical']),
            new \Twig_SimpleFunction('countwidgets',       [Runtime\WidgetRuntime::class, 'countWidgets'], $safe),
            new \Twig_SimpleFunction('current',            [Runtime\RecordRuntime::class, 'current']),
            new \Twig_SimpleFunction('data',               [Runtime\AdminRuntime::class, 'addData']),
            new \Twig_SimpleFunction('dump',               [Runtime\UtilsRuntime::class, 'printDump']),
            new \Twig_SimpleFunction('excerpt',            [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new \Twig_SimpleFunction('fancybox',           [Runtime\ImageRuntime::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFunction('fields',             [Runtime\RecordRuntime::class, 'fields'], $env + $safe),
            new \Twig_SimpleFunction('file_exists',        [Runtime\UtilsRuntime::class, 'fileExists'], $deprecated),
            new \Twig_SimpleFunction('firebug',            [Runtime\UtilsRuntime::class, 'printFirebug']),
            new \Twig_SimpleFunction('first',              'twig_first', $env + $deprecated),
            new \Twig_SimpleFunction('getuser',            [Runtime\UserRuntime::class, 'getUser']),
            new \Twig_SimpleFunction('getuserid',          [Runtime\UserRuntime::class, 'getUserId']),
            new \Twig_SimpleFunction('getwidgets',         [Runtime\WidgetRuntime::class, 'getWidgets'], $safe),
            new \Twig_SimpleFunction('haswidgets',         [Runtime\WidgetRuntime::class, 'hasWidgets'], $safe),
            new \Twig_SimpleFunction('hattr',              [Runtime\AdminRuntime::class, 'hattr'], $safe),
            new \Twig_SimpleFunction('hclass',             [Runtime\AdminRuntime::class, 'hclass'], $safe),
            new \Twig_SimpleFunction('htmllang',           [Runtime\HtmlRuntime::class, 'htmlLang']),
            new \Twig_SimpleFunction('image',              [Runtime\ImageRuntime::class, 'image']),
            new \Twig_SimpleFunction('imageinfo',          [Runtime\ImageRuntime::class, 'imageInfo']),
            new \Twig_SimpleFunction('isallowed',          [Runtime\UserRuntime::class, 'isAllowed']),
            new \Twig_SimpleFunction('ischangelogenabled', [Runtime\AdminRuntime::class, 'isChangelogEnabled'], $deprecated),
            new \Twig_SimpleFunction('ismobileclient',     [Runtime\HtmlRuntime::class, 'isMobileClient']),
            new \Twig_SimpleFunction('last',               'twig_last', $env + $deprecated),
            new \Twig_SimpleFunction('link',               [Runtime\HtmlRuntime::class, 'link'], $safe),
            new \Twig_SimpleFunction('listtemplates',      [Runtime\RecordRuntime::class, 'listTemplates']),
            new \Twig_SimpleFunction('markdown',           [Runtime\HtmlRuntime::class, 'markdown'], $safe),
            new \Twig_SimpleFunction('menu',               [Runtime\HtmlRuntime::class, 'menu'], $env + $safe),
            new \Twig_SimpleFunction('popup',              [Runtime\ImageRuntime::class, 'popup'], $safe),
            new \Twig_SimpleFunction('pager',              [Runtime\RecordRuntime::class, 'pager'], $env),
            new \Twig_SimpleFunction('print',              [Runtime\UtilsRuntime::class, 'printDump'], $deprecated + ['alternative' => 'dump']),
            new \Twig_SimpleFunction('randomquote',        [Runtime\AdminRuntime::class, 'randomQuote'], $safe),
            new \Twig_SimpleFunction('redirect',           [Runtime\UtilsRuntime::class, 'redirect'], $safe),
            new \Twig_SimpleFunction('request',            [Runtime\UtilsRuntime::class, 'request']),
            new \Twig_SimpleFunction('showimage',          [Runtime\ImageRuntime::class, 'showImage'], $safe),
            new \Twig_SimpleFunction('stack',              [Runtime\AdminRuntime::class, 'stack']),
            new \Twig_SimpleFunction('thumbnail',          [Runtime\ImageRuntime::class, 'thumbnail']),
            new \Twig_SimpleFunction('token',              [Runtime\UserRuntime::class, 'token'], $deprecated + ['alternative' => 'csrf_token']),
            new \Twig_SimpleFunction('trimtext',           [Runtime\RecordRuntime::class, 'trim'], $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFunction('unique',             [Runtime\ArrayRuntime::class, 'unique'], $safe),
            new \Twig_SimpleFunction('widgets',            [Runtime\WidgetRuntime::class, 'widgets'], $safe),
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
            new \Twig_SimpleFilter('__',             [Runtime\AdminRuntime::class, 'trans']),
            new \Twig_SimpleFilter('current',        [Runtime\RecordRuntime::class, 'current']),
            new \Twig_SimpleFilter('editable',       [Runtime\HtmlRuntime::class, 'editable'], $safe),
            new \Twig_SimpleFilter('excerpt',        [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new \Twig_SimpleFilter('fancybox',       [Runtime\ImageRuntime::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFilter('image',          [Runtime\ImageRuntime::class, 'image']),
            new \Twig_SimpleFilter('imageinfo',      [Runtime\ImageRuntime::class, 'imageInfo']),
            new \Twig_SimpleFilter('json_decode',    [Runtime\TextRuntime::class, 'jsonDecode']),
            new \Twig_SimpleFilter('localdate',      [Runtime\TextRuntime::class, 'localeDateTime'], $safe + $deprecated + ['alternative' => 'localedatetime']),
            new \Twig_SimpleFilter('localedatetime', [Runtime\TextRuntime::class, 'localeDateTime'], $safe),
            new \Twig_SimpleFilter('loglevel',       [Runtime\AdminRuntime::class, 'logLevel']),
            new \Twig_SimpleFilter('markdown',       [Runtime\HtmlRuntime::class, 'markdown'], $safe),
            new \Twig_SimpleFilter('order',          [Runtime\ArrayRuntime::class, 'order']),
            new \Twig_SimpleFilter('popup',          [Runtime\ImageRuntime::class, 'popup'], $safe),
            new \Twig_SimpleFilter('preg_replace',   [Runtime\TextRuntime::class, 'pregReplace']),
            new \Twig_SimpleFilter('safestring',     [Runtime\TextRuntime::class, 'safeString'], $safe),
            new \Twig_SimpleFilter('selectfield',    [Runtime\RecordRuntime::class, 'selectField']),
            new \Twig_SimpleFilter('showimage',      [Runtime\ImageRuntime::class, 'showImage'], $safe),
            new \Twig_SimpleFilter('shuffle',        [Runtime\ArrayRuntime::class, 'shuffle']),
            new \Twig_SimpleFilter('shy',            [Runtime\HtmlRuntime::class, 'shy'], $safe),
            new \Twig_SimpleFilter('slug',           [Runtime\TextRuntime::class, 'slug']),
            new \Twig_SimpleFilter('thumbnail',      [Runtime\ImageRuntime::class, 'thumbnail']),
            new \Twig_SimpleFilter('trimtext',       [Runtime\RecordRuntime::class, 'trim'], $safe + $deprecated + ['alternative' => 'excerpt']),
            new \Twig_SimpleFilter('tt',             [Runtime\HtmlRuntime::class, 'decorateTT'], $safe),
            new \Twig_SimpleFilter('twig',           [Runtime\HtmlRuntime::class, 'twig'], $safe),
            new \Twig_SimpleFilter('ucfirst',        'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
            new \Twig_SimpleFilter('ymllink',        [Runtime\AdminRuntime::class, 'ymllink'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json',      [Runtime\TextRuntime::class, 'testJson']),
            new \Twig_SimpleTest('stackable', [Runtime\AdminRuntime::class, 'testStackable']),
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
