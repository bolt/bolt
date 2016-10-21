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
            new \Twig_SimpleFunction('countwidgets',       [$this, 'countWidgets'],  $safe),
            new \Twig_SimpleFunction('current',            [$this, 'current']),
            new \Twig_SimpleFunction('data',               [$this, 'addData']),
            new \Twig_SimpleFunction('dump',               [$this, 'printDump']),
            new \Twig_SimpleFunction('excerpt',            [$this, 'excerpt'],     $safe),
            new \Twig_SimpleFunction('fancybox',           [$this, 'popup'],       $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFunction('fields',             [$this, 'fields'],      $env + $safe),
            new \Twig_SimpleFunction('file_exists',        [$this, 'fileExists']),
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
            new \Twig_SimpleFunction('stacked',            [$this, 'stacked']),
            new \Twig_SimpleFunction('stackitems',         [$this, 'stackItems']),
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
            new \Twig_SimpleFilter('twig',           [$this, 'twig'],              $safe),
            new \Twig_SimpleFilter('ucfirst',        'twig_capitalize_string_filter', $env + $deprecated + ['alternative' => 'capitalize']),
            new \Twig_SimpleFilter('ymllink',        [$this, 'ymllink'],           $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json', [$this, 'testJson']),
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
            'bolt_name'    => null,
            'bolt_version' => null,
            'bolt_stable'  => null,
            'frontend'     => null,
            'backend'      => null,
            'async'        => null,
            'paths'        => null,
            'theme'        => null,
            'user'         => null,
            'users'        => null,
            'config'       => null,
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

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::addData()
     */
    public function addData($path, $value)
    {
        $this->handlers['admin']->addData($path, $value);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::buid()
     */
    public function buid()
    {
        return $this->handlers['admin']->buid();
    }

    /**
     * @see \Bolt\Twig\Handler\WidgetHandler::countWidgets()
     */
    public function countWidgets($location = null, $zone = 'frontend')
    {
        return $this->handlers['widget']->countWidgets($location, $zone);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::current()
     */
    public function current($content)
    {
        return $this->handlers['record']->current($content);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::decorateTT()
     */
    public function decorateTT($str)
    {
        return $this->handlers['html']->decorateTT($str);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::editable()
     */
    public function editable($html, $content, $field)
    {
        return $this->handlers['html']->editable($html, $content, $field, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::excerpt()
     */
    public function excerpt($content, $length = 200, $focus = null)
    {
        return $this->handlers['record']->excerpt($content, $length, $focus);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::fields()
     */
    public function fields(\Twig_Environment $env, $record = null, $common = true, $extended = false, $repeaters = true, $templatefields = true, $template = '_sub_fields.twig', $exclude = null, $skip_uses = true)
    {
        return $this->handlers['record']->fields($env, $record, $common, $extended, $repeaters, $templatefields, $template, $exclude, $skip_uses);
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::fileExists()
     */
    public function fileExists($fn)
    {
        return $this->handlers['utils']->fileExists($fn, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\UserHandler::getUser()
     */
    public function getUser($who)
    {
        return $this->handlers['user']->getUser($who);
    }

    /**
     * @see \Bolt\Twig\Handler\UserHandler::getUserId()
     */
    public function getUserId($who)
    {
        return $this->handlers['user']->getUserId($who);
    }

    /**
     * @see \Bolt\Twig\Handler\WidgetHandler::getWidgets()
     */
    public function getWidgets()
    {
        return $this->handlers['widget']->getWidgets();
    }

    /**
     * @see \Bolt\Twig\Handler\WidgetHandler::hasWidgets()
     */
    public function hasWidgets($location = null, $zone = 'frontend')
    {
        return $this->handlers['widget']->hasWidgets($location, $zone);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::hattr()
     */
    public function hattr($attributes)
    {
        return $this->handlers['admin']->hattr($attributes);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::hclass()
     */
    public function hclass($classes)
    {
        return $this->handlers['admin']->hclass($classes);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::htmlLang()
     */
    public function htmlLang()
    {
        return $this->handlers['html']->htmlLang();
    }

    /**
     * @see \Bolt\Twig\Handler\ImageHandler::image()
     */
    public function image($filename = null, $width = null, $height = null, $crop = null)
    {
        return $this->handlers['image']->image($filename, $width, $height, $crop);
    }

    /**
     * @see \Bolt\Twig\Handler\ImageHandler::imageInfo()
     */
    public function imageInfo($filename)
    {
        return $this->handlers['image']->imageInfo($filename, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\UserHandler::isAllowed()
     */
    public function isAllowed($what, $content = null)
    {
        return $this->handlers['user']->isAllowed($what, $content);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Just use config instead.
     * @see \Bolt\Twig\Handler\AdminHandler::isChangelogEnabled()
     */
    public function isChangelogEnabled()
    {
        return $this->handlers['admin']->isChangelogEnabled();
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::isMobileClient()
     */
    public function isMobileClient()
    {
        return $this->handlers['html']->isMobileClient();
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::jsonDecode()
     */
    public function jsonDecode($string)
    {
        return $this->handlers['text']->jsonDecode($string);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::link()
     */
    public function link($location, $label = '[link]')
    {
        return $this->handlers['html']->link($location, $label);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::listTemplates()
     */
    public function listTemplates($filter = '')
    {
        return $this->handlers['record']->listTemplates($filter, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::localeDateTime()
     */
    public function localeDateTime($dateTime, $format = '%B %e, %Y %H:%M')
    {
        return $this->handlers['text']->localeDateTime($dateTime, $format);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::logLevel()
     */
    public function logLevel($level)
    {
        return $this->handlers['admin']->logLevel($level);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::markdown()
     */
    public function markdown($content)
    {
        return $this->handlers['html']->markdown($content);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::menu()
     */
    public function menu(\Twig_Environment $env, $identifier = '', $template = '_sub_menu.twig', $params = [])
    {
        return $this->handlers['html']->menu($env, $identifier, $template, $params, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\ArrayHandler::order()
     */
    public function order($array, $on, $onSecondary = '')
    {
        return $this->handlers['array']->order($array, $on, $onSecondary);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::pager()
     */
    public function pager(\Twig_Environment $env, $pagerName = '', $surr = 4, $template = '_sub_pager.twig', $class = '')
    {
        return $this->handlers['record']->pager($env, $pagerName, $surr, $template, $class);
    }

    /**
     * @see \Bolt\Twig\Handler\ImageHandler::popup()
     */
    public function popup($filename = null, $width = null, $height = null, $crop = null, $title = null)
    {
        return $this->handlers['image']->popup($filename, $width, $height, $crop, $title);
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::pregReplace()
     */
    public function pregReplace($str, $pattern, $replacement = '', $limit = -1)
    {
        return $this->handlers['text']->pregReplace($str, $pattern, $replacement, $limit);
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::printBacktrace()
     */
    public function printBacktrace($depth = 15)
    {
        return $this->handlers['utils']->printBacktrace($depth, $this->safe);
    }

    /**
     * Just for safe_twig. Main twig overrides this function.
     *
     * @see \Bolt\Provider\TwigServiceProvider
     */
    public function printDump()
    {
        return null;
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::printFirebug()
     */
    public function printFirebug($var, $msg = '')
    {
        return $this->handlers['utils']->printFirebug($var, $msg, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::randomQuote()
     */
    public function randomQuote()
    {
        return $this->handlers['admin']->randomQuote();
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::redirect()
     */
    public function redirect($path)
    {
        return $this->handlers['utils']->redirect($path, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::request()
     */
    public function request($parameter, $from = '', $stripslashes = false)
    {
        return $this->handlers['utils']->request($parameter, $from, $stripslashes, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::safeString()
     */
    public function safeString($str, $strict = false, $extrachars = '')
    {
        return $this->handlers['text']->safeString($str, $strict, $extrachars);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::selectField()
     */
    public function selectField($content, $fieldname, $startempty = false, $keyname = 'id')
    {
        return $this->handlers['record']->selectField($content, $fieldname, $startempty, $keyname);
    }

    /**
     * @see \Bolt\Twig\Handler\ImageHandler::showImage()
     */
    public function showImage($filename = null, $width = null, $height = null, $crop = null)
    {
        return $this->handlers['image']->showImage($filename, $width, $height, $crop);
    }

    /**
     * @see \Bolt\Twig\Handler\ArrayHandler::shuffle()
     */
    public function shuffle($array)
    {
        return $this->handlers['array']->shuffle($array);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::shy()
     */
    public function shy($str)
    {
        return $this->handlers['html']->shy($str);
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::slug()
     */
    public function slug($str)
    {
        return $this->handlers['text']->slug($str);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::stacked()
     */
    public function stacked($filename)
    {
        return $this->handlers['admin']->stacked($filename);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::stackItems()
     */
    public function stackItems($amount = 20, $type = '')
    {
        return $this->handlers['admin']->stackItems($amount, $type);
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::testJson()
     */
    public function testJson($string)
    {
        return $this->handlers['text']->testJson($string);
    }

    /**
     * @see \Bolt\Twig\Handler\ImageHandler::thumbnail()
     */
    public function thumbnail($filename = null, $width = null, $height = null, $crop = null)
    {
        return $this->handlers['image']->thumbnail($filename, $width, $height, $crop);
    }

    /**
     * @see \Bolt\Twig\Handler\UserHandler::token()
     */
    public function token()
    {
        return $this->handlers['user']->token();
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::trans()
     */
    public function trans()
    {
        $args = func_get_args();
        $numArgs = func_num_args();

        return $this->handlers['admin']->trans($args, $numArgs);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see \Bolt\Twig\TwigExtension::excerpt} instead
     */
    public function trim($content, $length = 200)
    {
        return $this->excerpt($content, $length);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::twig()
     */
    public function twig($snippet, $extravars = [])
    {
        return $this->handlers['html']->twig($snippet, $extravars);
    }

    /**
     * @see \Bolt\Twig\Handler\ArrayHandler::unique()
     */
    public function unique($array1, $array2)
    {
        return $this->handlers['array']->unique($array1, $array2);
    }

    /**
     * @see \Bolt\Twig\Handler\WidgetHandler::widgets()
     */
    public function widgets($location = null, $zone = 'frontend', $wrapper = 'widgetwrapper.twig')
    {
        return $this->handlers['widget']->widgets($location, $zone, $wrapper);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::ymllink()
     */
    public function ymllink($str)
    {
        return $this->handlers['admin']->ymllink($str, $this->safe);
    }
}
