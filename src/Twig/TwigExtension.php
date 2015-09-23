<?php

namespace Bolt\Twig;

use Bolt\Controller\Zone;
use Silex;
use Symfony\Component\HttpFoundation\RequestStack;

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
        $safe = ['is_safe' => ['html']];
        $env  = ['needs_environment' => true];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('__',                 [$this, 'trans'],       $safe),
            new \Twig_SimpleFunction('backtrace',          [$this, 'printBacktrace']),
            new \Twig_SimpleFunction('cachehash',          [$this, 'cacheHash'],   $safe),
            new \Twig_SimpleFunction('current',            [$this, 'current']),
            new \Twig_SimpleFunction('data',               [$this, 'addData']),
            new \Twig_SimpleFunction('dump',               [$this, 'printDump']),
            new \Twig_SimpleFunction('excerpt',            [$this, 'excerpt'],     $safe),
            new \Twig_SimpleFunction('fancybox',           [$this, 'popup'],       $safe), // "Fancybox" is deprecated.
            new \Twig_SimpleFunction('file_exists',        [$this, 'fileExists']),
            new \Twig_SimpleFunction('firebug',            [$this, 'printFirebug']),
            new \Twig_SimpleFunction('first',              [$this, 'first']),
            new \Twig_SimpleFunction('getuser',            [$this, 'getUser']),
            new \Twig_SimpleFunction('getuserid',          [$this, 'getUserId']),
            new \Twig_SimpleFunction('hattr',              [$this, 'hattr'],       $safe),
            new \Twig_SimpleFunction('hclass',             [$this, 'hclass'],      $safe),
            new \Twig_SimpleFunction('htmllang',           [$this, 'htmlLang']),
            new \Twig_SimpleFunction('image',              [$this, 'image']),
            new \Twig_SimpleFunction('imageinfo',          [$this, 'imageInfo']),
            new \Twig_SimpleFunction('isallowed',          [$this, 'isAllowed']),
            new \Twig_SimpleFunction('ischangelogenabled', [$this, 'isChangelogEnabled']),
            new \Twig_SimpleFunction('ismobileclient',     [$this, 'isMobileClient']),
            new \Twig_SimpleFunction('last',               [$this, 'last']),
            new \Twig_SimpleFunction('listcontent',        [$this, 'listContent']),
            new \Twig_SimpleFunction('listtemplates',      [$this, 'listTemplates']),
            new \Twig_SimpleFunction('markdown',           [$this, 'markdown'],    $safe),
            new \Twig_SimpleFunction('menu',               [$this, 'menu'],        array_merge($env, $safe)),
            new \Twig_SimpleFunction('pager',              [$this, 'pager'],       $env),
            new \Twig_SimpleFunction('popup',              [$this, 'popup'],       $safe),
            new \Twig_SimpleFunction('print',              [$this, 'printDump']),           // Deprecated.
            new \Twig_SimpleFunction('randomquote',        [$this, 'randomQuote'], $safe),
            new \Twig_SimpleFunction('redirect',           [$this, 'redirect'],    $safe),
            new \Twig_SimpleFunction('request',            [$this, 'request']),
            new \Twig_SimpleFunction('showimage',          [$this, 'showImage'],   $safe),
            new \Twig_SimpleFunction('stacked',            [$this, 'stacked']),
            new \Twig_SimpleFunction('stackitems',         [$this, 'stackItems']),
            new \Twig_SimpleFunction('thumbnail',          [$this, 'thumbnail']),
            new \Twig_SimpleFunction('token',              [$this, 'token'],       ['deprecated' => true]),
            new \Twig_SimpleFunction('trimtext',           [$this, 'trim'],        $safe),  // Deprecated.
            new \Twig_SimpleFunction('widget',             [$this, 'widget'])
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getFilters()
    {
        $safe = ['is_safe' => ['html']];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFilter('__',             [$this, 'trans']),
            new \Twig_SimpleFilter('cachehash',      [$this, 'cacheHash'],         $safe),
            new \Twig_SimpleFilter('current',        [$this, 'current']),
            new \Twig_SimpleFilter('editable',       [$this, 'editable'],          $safe),
            new \Twig_SimpleFilter('excerpt',        [$this, 'excerpt'],           $safe),
            new \Twig_SimpleFilter('fancybox',       [$this, 'popup'],             $safe), // "Fancybox" is deprecated.
            new \Twig_SimpleFilter('first',          [$this, 'first']),
            new \Twig_SimpleFilter('image',          [$this, 'image']),
            new \Twig_SimpleFilter('imageinfo',      [$this, 'imageInfo']),
            new \Twig_SimpleFilter('json_decode',    [$this, 'jsonDecode']),
            new \Twig_SimpleFilter('last',           [$this, 'last']),
            new \Twig_SimpleFilter('localdate',      [$this, 'localeDateTime'],    $safe),
            new \Twig_SimpleFilter('localedatetime', [$this, 'localeDateTime'],    $safe), // Deprecated
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
            new \Twig_SimpleFilter('trimtext',       [$this, 'trim'],              $safe), // Deprecated.
            new \Twig_SimpleFilter('tt',             [$this, 'decorateTT'],        $safe),
            new \Twig_SimpleFilter('twig',           [$this, 'twig'],              $safe),
            new \Twig_SimpleFilter('ucfirst',        [$this, 'ucfirst']),
            new \Twig_SimpleFilter('ymllink',        [$this, 'ymllink'],           $safe)
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json', [$this, 'testJson'])
        ];
    }

    public function getGlobals()
    {
        /** @var \Bolt\Config $config */
        $config = $this->app['config'];
        $configVal = $this->safe ? null : $config;
        /** @var \Bolt\Users $users */
        $users = $this->app['users'];
        /** @var \Bolt\Configuration\ResourceManager $resources */
        $resources = $this->app['resources'];

        $zone = null;
        /** @var RequestStack $requestStack */
        $requestStack = $this->app['request_stack'];
        if ($request = $requestStack->getCurrentRequest()) {
            $zone = Zone::get($request);
        }

        // User calls can cause exceptions that block the exception handler
        try {
            /** @deprecated Since 2.3 to be removed in 3.0 */
            $usersVal = $this->safe ? null : $users->getUsers();
            $usersCur = $users->getCurrentUser();
        } catch (\Exception $e) {
            $usersVal = null;
            $usersCur = null;
        }

        // Structured to allow PHPStorm's SymfonyPlugin to provide code completion
        return [
            'bolt_name'    => $this->app['bolt_name'],
            'bolt_version' => $this->app['bolt_version'],
            'frontend'     => $zone === Zone::FRONTEND,
            'backend'      => $zone === Zone::BACKEND,
            'async'        => $zone === Zone::ASYNC,
            'paths'        => $resources->getPaths(),
            'theme'        => $config->get('theme'),
            'user'         => $usersCur,
            'users'        => $usersVal,
            'config'       => $configVal,
        ];
    }

    public function getTokenParsers()
    {
        $parsers = [];
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
     * @see \Bolt\Twig\Handler\HtmlHandler::cacheHash()
     */
    public function cacheHash($fileName)
    {
        return $this->handlers['html']->cacheHash($fileName);
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
    public function twig($snippet, $extravars = [])
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
