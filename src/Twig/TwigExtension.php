<?php

namespace Bolt\Twig;

use Silex;

/**
 * The class for Bolt' Twig tags, functions and filters.
 */
class TwigExtension extends \Twig_Extension
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
        $safe = array('is_safe' => array('html'));
        $env  = array('needs_environment' => true);

        return array(
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('__',                 array($this, 'trans'),       $safe),
            new \Twig_SimpleFunction('backtrace',          array($this, 'printBacktrace')),
            new \Twig_SimpleFunction('current',            array($this, 'current')),
            new \Twig_SimpleFunction('data',               array($this, 'addData')),
            new \Twig_SimpleFunction('debugbar',           array($this, 'debugBar')),
            new \Twig_SimpleFunction('dump',               array($this, 'printDump')),
            new \Twig_SimpleFunction('excerpt',            array($this, 'excerpt'),     $safe),
            new \Twig_SimpleFunction('fancybox',           array($this, 'popup'),       $safe), // "Fancybox" is deprecated.
            new \Twig_SimpleFunction('file_exists',        array($this, 'fileExists')),
            new \Twig_SimpleFunction('firebug',            array($this, 'printFirebug')),
            new \Twig_SimpleFunction('first',              array($this, 'first')),
            new \Twig_SimpleFunction('getuser',            array($this, 'getUser')),
            new \Twig_SimpleFunction('getuserid',          array($this, 'getUserId')),
            new \Twig_SimpleFunction('htmllang',           array($this, 'htmlLang')),
            new \Twig_SimpleFunction('image',              array($this, 'image')),
            new \Twig_SimpleFunction('imageinfo',          array($this, 'imageInfo')),
            new \Twig_SimpleFunction('isallowed',          array($this, 'isAllowed')),
            new \Twig_SimpleFunction('ischangelogenabled', array($this, 'isChangelogEnabled')),
            new \Twig_SimpleFunction('ismobileclient',     array($this, 'isMobileClient')),
            new \Twig_SimpleFunction('last',               array($this, 'last')),
            new \Twig_SimpleFunction('listcontent',        array($this, 'listContent')),
            new \Twig_SimpleFunction('listtemplates',      array($this, 'listTemplates')),
            new \Twig_SimpleFunction('markdown',           array($this, 'markdown'),    $safe),
            new \Twig_SimpleFunction('menu',               array($this, 'menu'),        array_merge($env, $safe)),
            new \Twig_SimpleFunction('pager',              array($this, 'pager'),       $env),
            new \Twig_SimpleFunction('popup',              array($this, 'popup'),       $safe),
            new \Twig_SimpleFunction('print',              array($this, 'printDump')),           // Deprecated.
            new \Twig_SimpleFunction('randomquote',        array($this, 'randomQuote'), $safe),
            new \Twig_SimpleFunction('redirect',           array($this, 'redirect'),    $safe),
            new \Twig_SimpleFunction('request',            array($this, 'request')),
            new \Twig_SimpleFunction('showimage',          array($this, 'showImage'),   $safe),
            new \Twig_SimpleFunction('stacked',            array($this, 'stacked')),
            new \Twig_SimpleFunction('stackitems',         array($this, 'stackItems')),
            new \Twig_SimpleFunction('thumbnail',          array($this, 'thumbnail')),
            new \Twig_SimpleFunction('token',              array($this, 'token')),
            new \Twig_SimpleFunction('trimtext',           array($this, 'trim'),        $safe),  // Deprecated.
            new \Twig_SimpleFunction('widget',             array($this, 'widget'))
            // @codingStandardsIgnoreEnd
        );
    }

    public function getFilters()
    {
        $safe = array('is_safe' => array('html'));

        return array(
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFilter('__',             array($this, 'trans')),
            new \Twig_SimpleFilter('current',        array($this, 'current')),
            new \Twig_SimpleFilter('editable',       array($this, 'editable'),          $safe),
            new \Twig_SimpleFilter('excerpt',        array($this, 'excerpt'),           $safe),
            new \Twig_SimpleFilter('fancybox',       array($this, 'popup'),             $safe), // "Fancybox" is deprecated.
            new \Twig_SimpleFilter('first',          array($this, 'first')),
            new \Twig_SimpleFilter('image',          array($this, 'image')),
            new \Twig_SimpleFilter('imageinfo',      array($this, 'imageInfo')),
            new \Twig_SimpleFilter('json_decode',    array($this, 'jsonDecode')),
            new \Twig_SimpleFilter('last',           array($this, 'last')),
            new \Twig_SimpleFilter('localdate',      array($this, 'localeDateTime'),    $safe),
            new \Twig_SimpleFilter('localedatetime', array($this, 'localeDateTime'),    $safe), // Deprecated
            new \Twig_SimpleFilter('loglevel',       array($this, 'logLevel')),
            new \Twig_SimpleFilter('markdown',       array($this, 'markdown'),          $safe),
            new \Twig_SimpleFilter('order',          array($this, 'order')),
            new \Twig_SimpleFilter('popup',          array($this, 'popup'),             $safe),
            new \Twig_SimpleFilter('preg_replace',   array($this, 'pregReplace')),
            new \Twig_SimpleFilter('safestring',     array($this, 'safeString'),        $safe),
            new \Twig_SimpleFilter('selectfield',    array($this, 'selectField')),
            new \Twig_SimpleFilter('showimage',      array($this, 'showImage'),         $safe),
            new \Twig_SimpleFilter('shuffle',        array($this, 'shuffle')),
            new \Twig_SimpleFilter('shy',            array($this, 'shy'),               $safe),
            new \Twig_SimpleFilter('slug',           array($this, 'slug')),
            new \Twig_SimpleFilter('thumbnail',      array($this, 'thumbnail')),
            new \Twig_SimpleFilter('trimtext',       array($this, 'trim'),              $safe), // Deprecated.
            new \Twig_SimpleFilter('tt',             array($this, 'decorateTT'),        $safe),
            new \Twig_SimpleFilter('twig',           array($this, 'twig'),              $safe),
            new \Twig_SimpleFilter('ucfirst',        array($this, 'ucfirst')),
            new \Twig_SimpleFilter('ymllink',        array($this, 'ymllink'),           $safe)
            // @codingStandardsIgnoreEnd
        );
    }

    public function getTests()
    {
        return array(
            new \Twig_SimpleTest('json', array($this, 'testJson'))
        );
    }

    public function getGlobals()
    {
        /** @var Config $config */
        $config = $this->app['config'];
        /** @var Users $users */
        $users = $this->app['users'];
        /** @var Configuration\ResourceManager $resources */
        $resources = $this->app['resources'];

        $configVal = $this->safe ? null : $config;
        $usersVal = $this->safe ? null : $users->getUsers();

        // Structured to allow PHPStorm's SymfonyPlugin to provide code completion
        return array(
            'bolt_name'            => $this->app['bolt_name'],
            'bolt_version'         => $this->app['bolt_version'],
            'frontend'             => false,
            'backend'              => false,
            'async'                => false,
            $config->getWhichEnd() => true,
            'paths'                => $resources->getPaths(),
            'theme'                => $config->get('theme'),
            'user'                 => $users->getCurrentUser(),
            'users'                => $usersVal,
            'config'               => $configVal,
        );
    }

    public function getTokenParsers()
    {
        $parsers = array();
        if (!$this->safe) {
            $parsers[] = new SetcontentTokenParser();
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
     * @see \Bolt\Twig\Handler\RecordHandler::current()
     */
    public function current($content)
    {
        return $this->handlers['record']->current($content);
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::debugBar()
     */
    public function debugBar($value)
    {
        $this->handlers['utils']->debugBar($value);
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
    public function excerpt($content, $length = 200)
    {
        return $this->handlers['record']->excerpt($content, $length);
    }

    /**
     * @see \Bolt\Twig\Handler\UtilsHandler::fileExists()
     */
    public function fileExists($fn)
    {
        return $this->handlers['utils']->fileExists($fn, $this->safe);
    }

    /**
     * @see \Bolt\Twig\Handler\ArrayHandler::first()
     */
    public function first($array)
    {
        return $this->handlers['array']->first($array);
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
     * @see \Bolt\Twig\Handler\HtmlHandler::htmlLang()
     */
    public function htmlLang()
    {
        return $this->handlers['html']->htmlLang();
    }

    /**
     * @see \Bolt\Twig\Handler\ImageHandler::image()
     */
    public function image($filename, $width = '', $height = '', $crop = '')
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
     * @see \Bolt\Twig\Handler\ArrayHandler::last()
     */
    public function last($array)
    {
        return $this->handlers['array']->last($array);
    }

    /**
     * @see \Bolt\Twig\Handler\RecordHandler::listContent()
     */
    public function listContent($contenttype, $relationoptions, $content)
    {
        return $this->handlers['record']->listContent($contenttype, $relationoptions, $content);
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
    public function menu(\Twig_Environment $env, $identifier = '', $template = '_sub_menu.twig', $params = array())
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
    public function popup($filename = '', $width = 100, $height = 100, $crop = '', $title = '')
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
     * @see \Bolt\Twig\Handler\UtilsHandler::printDump()
     */
    public function printDump($var)
    {
        return $this->handlers['utils']->printDump($var, $this->safe);
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
    public function showImage($filename = '', $width = 0, $height = 0, $crop = '')
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
    public function thumbnail($filename, $width = '', $height = '', $zoomcrop = 'crop')
    {
        return $this->handlers['image']->thumbnail($filename, $width, $height, $zoomcrop);
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
     * @see \Bolt\Twig\Handler\RecordHandler::trim()
     */
    public function trim($content, $length = 200)
    {
        return $this->handlers['record']->trim($content, $length);
    }

    /**
     * @see \Bolt\Twig\Handler\HtmlHandler::twig()
     */
    public function twig($snippet, $extravars = array())
    {
        return $this->handlers['html']->twig($snippet, $extravars);
    }

    /**
     * @see \Bolt\Twig\Handler\TextHandler::ucfirst()
     */
    public function ucfirst($str)
    {
        return $this->handlers['text']->ucfirst($str);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::widget()
     */
    public function widget($type = '', $location = '')
    {
        return $this->handlers['admin']->widget($type, $location);
    }

    /**
     * @see \Bolt\Twig\Handler\AdminHandler::ymllink()
     */
    public function ymllink($str)
    {
        return $this->handlers['admin']->ymllink($str, $this->safe);
    }
}
