<?php

namespace Bolt\Twig\Handler;

use Bolt\Application;
use Bolt\Helpers\Html;
use Bolt\Helpers\Str;
use Bolt\Legacy\Content;
use Maid\Maid;
use Silex;

/**
 * Bolt specific Twig functions and filters for HTML
 *
 * @internal
 */
class HtmlHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Take a file name and add a HTML query paramter with a unique hash based
     * on the site's salt value and the file modification time, or file name
     * if the file can't be found by the function.
     *
     * @param string $fileName
     *
     * @return string
     */
    public function cacheHash($fileName)
    {
        $fullPath = $this->app['resources']->getPath('root') . '/' . $fileName;

        if (is_readable($fullPath)) {
            return "$fileName?v=" . $this->app['asset.file.hash']($fullPath);
        } elseif (is_readable($fileName)) {
            return "$fileName?v=" . $this->app['asset.file.hash']($fileName);
        }
    }

    /**
     * Transforms plain text to HTML
     *
     * @see Bolt\Helpers\Html::decorateTT()
     *
     * @param string $str
     *
     * @return string
     */
    public function decorateTT($str)
    {
        return Html::decorateTT($str);
    }

    /**
     * Makes a piece of HTML editable.
     *
     * @param string               $html    The HTML to be editable
     * @param \Bolt\Legacy\Content $content The actual content
     * @param string               $field
     * @param boolean              $safe
     *
     * @return string
     */
    public function editable($html, Content $content, $field, $safe)
    {
        // Editing content from within content? NOPE NOPE NOPE.
        if ($safe) {
            return null;
        }

        $contenttype = $content->contenttype['slug'];

        $output = sprintf(
            '<div class="Bolt-editable" data-id="%s" data-contenttype="%s" data-field="%s">%s</div>',
            $content->id,
            $contenttype,
            $field,
            $html
        );

        return $output;
    }

    /**
     * Returns the language value for in tags where the language attribute is
     * required. The underscore '_' in the locale will be replaced with a
     * hyphen '-'.
     *
     * @return string
     */
    public function htmlLang()
    {
        return str_replace('_', '-', $this->app['locale']);
    }

    /**
     * Check if the page is viewed on a mobile device.
     *
     * @return boolean
     */
    public function isMobileClient()
    {
        if (preg_match(
            '/(android|blackberry|htc|iemobile|iphone|ipad|ipaq|ipod|nokia|playbook|smartphone)/i',
            $_SERVER['HTTP_USER_AGENT']
        )) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Formats the given string as Markdown in HTML.
     *
     * @param string $content
     *
     * @return string Markdown output
     */
    public function markdown($content)
    {
        // Parse the field as Markdown, return HTML
        $output = $this->app['markdown']->text($content);

        $config = $this->app['config']->get('general/htmlcleaner');
        $allowed_tags = !empty($config['allowed_tags']) ? $config['allowed_tags'] :
            ['div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dh', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'];
        $allowed_attributes = !empty($config['allowed_attributes']) ? $config['allowed_attributes'] :
            ['id', 'class', 'name', 'value', 'href', 'src'];

        // Sanitize/clean the HTML.
        $maid = new Maid(
            [
                'output-format'   => 'html',
                'allowed-tags'    => $allowed_tags,
                'allowed-attribs' => $allowed_attributes
            ]
        );
        $output = $maid->clean($output);

        return $output;
    }

    /**
     * Output a menu.
     *
     * @param \Twig_Environment $env
     * @param string            $identifier Identifier for a particular menu
     * @param string            $template   The template to use.
     * @param array             $params     Extra parameters to pass on to the menu template.
     * @param boolean           $safe
     *
     * @return string|null
     */
    public function menu(\Twig_Environment $env, $identifier = '', $template = '_sub_menu.twig', $params = [], $safe)
    {
        if ($safe) {
            return null;
        }

        /** @var \Bolt\Helpers\Menu $menu */
        $menu = $this->app['menu']->menu($identifier);

        $twigvars = [
            'name' => $menu->getName(),
            'menu' => $menu->getItems()
        ];

        // If $params is not empty, merge it with twigvars.
        if (!empty($params) && is_array($params)) {
            $twigvars = $twigvars + $params;
        }

        return $env->render($template, $twigvars);
    }

    /**
     * Add 'soft hyphens' &shy; to a string, so that it won't break layout in HTML when
     * using strings without spaces or dashes.
     *
     * @param string $str
     *
     * @return string
     */
    public function shy($str)
    {
        if (is_string($str)) {
            $str = Str::shyphenate($str);
        }

        return $str;
    }

    /**
     * Formats the given string as Twig in HTML.
     *
     * Note: this is partially duplicating the template_from_string functionality:
     * http://twig.sensiolabs.org/doc/functions/template_from_string.html
     *
     * We can't use that functionality though, since it requires the Twig_Extension_StringLoader()
     * extension. If we would use that, when instantiating Twig, it screws up the rendering: Every
     * template that has a filename that doesn't exist will be rendered as literal string. This
     * _really_ messes up the 'cascading rendering' of our theme templates.
     *
     * @param $snippet
     * @param array $extravars
     *
     * @return string Twig output
     */
    public function twig($snippet, $extravars = [])
    {
        return $this->app['safe_render']->render($snippet, $extravars)->getContent();
    }
}
